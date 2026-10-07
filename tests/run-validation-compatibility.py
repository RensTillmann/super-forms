import argparse,json,os,subprocess,sys,hashlib
from pathlib import Path
p=argparse.ArgumentParser();p.add_argument('--plugin',required=True);p.add_argument('--bootstrap',required=True);p.add_argument('--group',default='hotfix');p.add_argument('--out',required=True);a=p.parse_args()
plugin=Path(a.plugin).resolve(); cases=[]
def case(id,tag,value,settings,accepted=True,**extra): cases.append(dict(id=id,tag=tag,value=value,settings=settings,accepted=accepted,**extra))
if a.group in ('hotfix','all'):
    for key,fmt in [('omitted',{}),('empty',{'format':''}),('custom-empty',{'format':'custom','custom_format':''}),('custom-omitted',{'format':'custom'}),('explicit',{'format':'dd-mm-yy'})]:
        case('hotfix-'+key,'date','27-09-2026',fmt,timestamp='1790467200000')
    case('explicit-slash','date','27/09/2026',{'format':'custom','custom_format':'dd/mm/yy'},timestamp='1790467200000')
    case('wrong-format','date','2026-09-27',{'format':'dd-mm-yy'},False)
    case('invalid-day','date','31-02-2026',{'format':'dd-mm-yy'},False)
if a.group in ('email','all'):
    for email,accept in [('ops@backup_mx1.corp.example.com',False),('ops@-backup.example.com',False),('ops@backup-.example.com',False),('ops@backup-mx1.example.com',True),("o\'neil@example.com",True),('ops_test+tag@example.technology',True)]:case('email-'+email,'text',email,{'validation':'email'},accept)
if a.group in ('currency','all'):
    for id,value,fmt,accept in [('us','$1,234.00',{},True),('eu','€1.234,56',{'currency':'€','thousand_separator':'.','decimal_separator':','},True),('eu-no-affix','1.234,56',{'currency':'','thousand_separator':'.','decimal_separator':','},True),('zero','$0.00',{},True),('group-bad','$1,,234.00',{},False),('mixed','$1,234.00oops',{},False),('empty','',{},True),('raw','1234.56',{},True)]:case('currency-'+id,'currency',value,dict(validation='float',**fmt),accept)
    case('currency-numeric-integer','currency','$1,234.00',{'validation':'numeric'},True)
    case('currency-numeric-fraction','currency','$1,234.50',{'validation':'numeric'},False)
    case('text-not-currency','text','$1,234.00',{'validation':'float'},False)
    case('slider-raw','slider','1234.56',{'validation':'float'},True)
if a.group in ('date','all'):
    for id,value,fmt,stamp in [('month-year','02-2026','mm-yy','1769904000000'),('year-only','2026','yy','1767225600000')]:case('date-'+id,'date',value,{'format':'custom','custom_format':fmt},timestamp=stamp)
    case('date-zero-month','date','00-2026',{'format':'custom','custom_format':'mm-yy'},False)
    case('date-bad-month','date','13-2026',{'format':'custom','custom_format':'mm-yy'},False)
if a.group in ('code','all'):
    base={'code':'true','code_length':'7','code_characters':'4','code_uppercase':'false','code_lowercase':'false','code_prefix':'','code_suffix':''}
    case('code-empty-charset','hidden','',base,True,code_pattern='^[A-Z]{7}$')
    for k,u,l,pattern in [('upper','true','false','^[A-Z]{7}$'),('lower','false','true','^[a-z]{7}$')]:case('code-'+k,'hidden','',dict(base,code_uppercase=u,code_lowercase=l),True,code_pattern=pattern)
if not cases:raise SystemExit('Unknown group '+a.group)
php='C:/php-8.3.12/php.exe'
env={k:os.environ[k] for k in ['PATH','SystemRoot','WINDIR','TEMP','TMP','TMPDIR'] if k in os.environ}
env.update(SF_PLUGIN_ROOT=plugin.as_posix(),SF_TEST_BOOTSTRAP=str(Path(a.bootstrap).resolve()))
results=[]
for c in cases:
    r=subprocess.run([php,'-d','extension=C:/php-8.3.12/ext/php_pdo_sqlite.dll','-d','extension=C:/php-8.3.12/ext/php_sqlite3.dll',str(Path(__file__).with_name('validation-compatibility-submit.php'))],input=json.dumps(c),text=True,encoding='utf-8',env=env,capture_output=True,timeout=30)
    accepted='CHECKS_ACCEPTED:' in r.stdout
    data=json.loads(r.stdout.split('CHECKS_ACCEPTED:')[-1]) if accepted else {}
    ok=accepted==c['accepted'] and r.returncode==0 and not r.stderr
    if ok and not accepted:
        try: rejection=json.loads(r.stdout)
        except ValueError: rejection={}
        ok=rejection.get('error') is True and rejection.get('msg')=='Invalid form data.'
    if ok and accepted and 'timestamp' in c:ok=data.get('timestamp')==c['timestamp']
    if ok and accepted and 'code_pattern' in c:
        import re
        ok=re.fullmatch(c['code_pattern'],data.get('value','')) is not None
    results.append(dict(case=c,ok=ok,exit=r.returncode,data=data,stdout=r.stdout,stderr=r.stderr))
    print(('PASS' if ok else 'FAIL'),c['id'], 'accepted='+str(accepted),'' if ok else r.stdout[:250]+' '+r.stderr[:250])
sourcefiles=['includes/class-ajax.php','includes/class-common.php','assets/js/common.js']
receipt=dict(plugin=str(plugin),group=a.group,php=php,source_hashes={f:hashlib.sha256((plugin/f).read_bytes()).hexdigest() for f in sourcefiles},cases=results,passed=sum(x['ok'] for x in results),total=len(results))
out=Path(a.out);out.parent.mkdir(parents=True,exist_ok=True)
if out.exists():raise SystemExit('Refuse to overwrite evidence: '+str(out))
out.write_text(json.dumps(receipt,indent=2),encoding='utf-8')
print(f"{receipt['passed']}/{receipt['total']} passed; receipt {out}")
sys.exit(0 if all(x['ok'] for x in results) else 1)
