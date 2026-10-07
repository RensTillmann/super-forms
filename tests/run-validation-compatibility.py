import argparse,json,os,subprocess,sys,hashlib,shutil
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
shared_cases=json.loads(Path(__file__).with_name('validation-compatibility-cases.json').read_text(encoding='utf-8'))
cases.extend(c for c in shared_cases if a.group=='all' or c['group']==a.group)
if a.group in ('date','all'):
    for id,value,fmt,stamp in [('month-year','02-2026','mm-yy','1769904000000'),('year-only','2026','yy','1767225600000')]:case('date-'+id,'date',value,{'format':'custom','custom_format':fmt},timestamp=stamp)
    case('date-zero-month','date','00-2026',{'format':'custom','custom_format':'mm-yy'},False)
    case('date-bad-month','date','13-2026',{'format':'custom','custom_format':'mm-yy'},False)
if a.group in ('code','all'):
    base={'enable_random_code':'true','code_length':'7','code_characters':'4','code_uppercase':'false','code_lowercase':'false','code_prefix':'','code_suffix':''}
    case('code-empty-charset','hidden','',base,True,code_pattern='^[A-Z]{7}$')
    import secrets
    case('code-preview-claim','hidden','',dict(base,code_prefix='contract-'+secrets.token_hex(8)+'-'),True,operation='code-claim')
    for k,u,l,pattern in [('upper','true','false','^[A-Z]{7}$'),('lower','false','true','^[a-z]{7}$')]:case('code-'+k,'hidden','',dict(base,code_uppercase=u,code_lowercase=l),True,code_pattern=pattern)
if not cases:raise SystemExit('Unknown group '+a.group)
php=shutil.which('php')
if not php:raise SystemExit('PHP executable not found')
php_command=[php]
if sys.platform=='win32':
    for extension in ['php_pdo_sqlite.dll','php_sqlite3.dll']:
        dll=Path(php).resolve().parent/'ext'/extension
        if not dll.exists():raise SystemExit('SQLite extension missing: '+str(dll))
        php_command+=['-d','extension='+str(dll)]
env={k:os.environ[k] for k in ['PATH','SystemRoot','WINDIR','TEMP','TMP','TMPDIR'] if k in os.environ}
env.update(SF_PLUGIN_ROOT=plugin.as_posix(),SF_TEST_BOOTSTRAP=str(Path(a.bootstrap).resolve()))
results=[]
for c in cases:
    r=subprocess.run(php_command+[str(Path(__file__).with_name('validation-compatibility-submit.php'))],input=json.dumps(c),text=True,encoding='utf-8',env=env,cwd=plugin,capture_output=True,timeout=30)
    accepted='CHECKS_ACCEPTED:' in r.stdout
    data=json.loads(r.stdout.split('CHECKS_ACCEPTED:')[-1]) if accepted else {}
    # Beta's inherited informational event log is retained, not treated as a warning.
    unexpected_stderr=[line for line in r.stderr.splitlines() if line!='triggerEvent(sf.before.submission)']
    ok=accepted==c['accepted'] and r.returncode==0 and not unexpected_stderr
    if ok and accepted and 'code_pattern' not in c and 'operation' not in c:ok=data.get('value')==c['value']
    if ok and not accepted:
        try: rejection=json.loads(r.stdout)
        except ValueError: rejection={}
        ok=rejection.get('error') is True and rejection.get('msg')=='Invalid form data.'
    if ok and accepted and 'timestamp' in c:ok=data.get('timestamp')==c['timestamp']
    if ok and accepted and c.get('operation')=='code-claim':ok=data.get('first_claim') is True and data.get('duplicate_claim') is False
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
