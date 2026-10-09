import argparse,json,os,subprocess,sys,hashlib,shutil
from pathlib import Path
p=argparse.ArgumentParser();p.add_argument('--plugin',required=True);p.add_argument('--bootstrap',required=True);p.add_argument('--group',default='hotfix');p.add_argument('--out',required=True);a=p.parse_args()
# Reserve evidence before executing any stateful child. Exclusive creation also
# closes the exists-check race; a failed setup may leave an empty reservation.
out=Path(a.out);out.parent.mkdir(parents=True,exist_ok=True)
try:
    evidence_stream=out.open('x',encoding='utf-8')
except FileExistsError:
    raise SystemExit('Refuse to overwrite evidence: '+str(out))
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
if a.group=='render': cases=[dict(c,operation='render-currency',accepted=True) for c in shared_cases if c['tag']=='currency']
if a.group in ('date','all'):
    for id,value,fmt,stamp in [('month-year','02-2026','mm-yy','1769904000000'),('year-only','2026','yy','1767225600000')]:case('date-'+id,'date',value,{'format':'custom','custom_format':fmt},timestamp=stamp)
    case('date-zero-month','date','00-2026',{'format':'custom','custom_format':'mm-yy'},False)
    case('date-bad-month','date','13-2026',{'format':'custom','custom_format':'mm-yy'},False)
    case('date-literal-only','date','not-a-date',{'format':'custom','custom_format':"'not-a-date'"},False)
    case('date-weekday-only','date','Monday',{'format':'custom','custom_format':'DD'},False)
    case('date-unix','date','1769904000000',{'format':'custom','custom_format':'@'},True,timestamp='1769904000000')
if a.group in ('code','all'):
    base={'enable_random_code':'true','code_length':'7','code_characters':'4','code_uppercase':'false','code_lowercase':'false','code_prefix':'','code_suffix':''}
    case('code-empty-charset','hidden','',base,True,code_pattern='^[A-Z]{7}$')
    import secrets
    case('code-preview-claim','hidden','',dict(base,code_prefix='contract-'+secrets.token_hex(8)+'-'),True,operation='code-claim')
    for k,u,l,pattern in [('upper','true','false','^[A-Z]{7}$'),('lower','false','true','^[a-z]{7}$')]:case('code-'+k,'hidden','',dict(base,code_uppercase=u,code_lowercase=l),True,code_pattern=pattern)
if a.group in ('layout','all'):
    # B2: fields inside Tabs/Accordion panes (saved inner = one list per pane). Every layout
    # case has a flat twin (same elements, layout wrapper removed) proving the payload is legitimate.
    def el(tag,data,inner=None,group='form_elements'):return dict(tag=tag,group=group,data=data,inner=inner or [])
    def text(n,req=False):return el('text',dict(name=n,email=n,validation='empty' if req else 'none',may_be_empty='false' if req else 'true'))
    def panes(layout,*ps):return el('tabs',dict(layout=layout,items=[dict(title='P'+str(i+1),desc='') for i in range(len(ps))]),list(ps),'layout_elements')
    def column(inner,**d):return el('column',dict(size='1/1',**d),inner,'layout_elements')
    def var(n,v,**x):return dict(name=n,value=v,type='var',**x)
    def flat(items):
        out=[]
        for e in items:
            if e['tag']=='tabs':
                for p in e['inner']:out+=flat(p)
            else:out.append(dict(e,inner=flat(e['inner'])))
        return out
    code=dict(enable_random_code='true',code_length='7',code_characters='4',code_uppercase='true',code_lowercase='false',code_prefix='',code_suffix='')
    plan=el('dropdown',dict(name='plan',validation='none',may_be_empty='true',dropdown_items=[dict(value='basic',label='Basic'),dict(value='pro',label='Pro')]))
    shown_if=dict(conditional_action='show',conditional_trigger='all',conditional_items=[dict(field='outside',logic='equal',value='show',and_method='',field_and='',logic_and='',value_and='')])
    layout_cases=[
        # id, elements, payload, accepted, extra
        ('tabs-text-required-tab2',[text('outside'),panes('tabs',[text('tab_a')],[text('tab_b_required',True)])],
            dict(outside=var('outside','hello'),tab_a=var('tab_a','alpha'),tab_b_required=var('tab_b_required','beta')),True,{}),
        ('accordion-item2',[text('outside'),panes('accordion',[text('acc_a')],[text('acc_b')])],
            dict(outside=var('outside','hello'),acc_a=var('acc_a',''),acc_b=var('acc_b','delta')),True,{}),
        ('tabs-file',[text('outside'),panes('tabs',[text('tab_a')],[el('file',dict(name='doc',may_be_empty='true'))])],
            dict(outside=var('outside','hello'),tab_a=var('tab_a','alpha'),doc=dict(name='doc',type='files',files=[])),True,{}),
        ('tabs-unique-code',[text('outside'),panes('tabs',[text('tab_a')],[el('hidden',dict(name='ref',**code))])],
            dict(outside=var('outside','hello'),tab_a=var('tab_a','alpha'),ref=var('ref','')),True,dict(field_patterns=dict(ref='^[A-Z]{7}$'))),
        ('tabs-dropdown',[text('outside'),panes('tabs',[text('tab_a')],[plan])],
            dict(outside=var('outside','hello'),tab_a=var('tab_a','alpha'),plan=var('plan','pro',selected_values=['pro'])),True,{}),
        ('tabs-repeater',[text('outside'),panes('tabs',[text('tab_a')],[column([text('guest')],duplicate='enabled')])],
            dict(outside=var('outside','hello'),tab_a=var('tab_a','alpha'),guest=var('guest','Ada'),
                 _super_dynamic_data=dict(guest=[dict(guest=var('guest','Ada'))])),True,dict(compare=['outside','tab_a','guest'])),
        ('tabs-conditional-hidden',[text('outside'),panes('tabs',[text('tab_a')],[column([text('cond_required',True)],**shown_if)])],
            dict(outside=var('outside','hello'),tab_a=var('tab_a','alpha')),True,{}),
        ('tabs-conditional-shown',[text('outside'),panes('tabs',[text('tab_a')],[column([text('cond_required',True)],**shown_if)])],
            dict(outside=var('outside','show'),tab_a=var('tab_a','alpha'),cond_required=var('cond_required','gamma')),True,{}),
        ('column-nested-tabs',[column([text('outside'),panes('tabs',[text('tab_a')],[text('tab_b_required',True)])])],
            dict(outside=var('outside','hello'),tab_a=var('tab_a','alpha'),tab_b_required=var('tab_b_required','beta')),True,{}),
        # Negatives: still rejected after the fix.
        ('tabs-required-empty',[text('outside'),panes('tabs',[text('tab_a')],[text('tab_b_required',True)])],
            dict(outside=var('outside','hello'),tab_a=var('tab_a','alpha'),tab_b_required=var('tab_b_required','')),False,dict(expected_msg='Please fill in all required fields.')),
        ('tabs-unknown-field',[text('outside'),panes('tabs',[text('tab_a')],[text('tab_b')])],
            dict(outside=var('outside','hello'),tab_a=var('tab_a','alpha'),tab_b=var('tab_b','beta'),injected=var('injected','x')),False,{}),
        ('tabs-dropdown-foreign-choice',[text('outside'),panes('tabs',[text('tab_a')],[plan])],
            dict(outside=var('outside','hello'),tab_a=var('tab_a','alpha'),plan=var('plan','enterprise',selected_values=['enterprise'])),False,{}),
    ]
    for id,elements,payload,accepted,extra in layout_cases:
        cases.append(dict(id='layout-'+id,tag='layout',value=None,settings={},accepted=accepted,elements=elements,payload=payload,**extra))
        cases.append(dict(id='layout-flat-'+id,tag='layout',value=None,settings={},accepted=accepted,elements=flat(elements),payload=payload,**extra))
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
def persist(status):
    sourcefiles=['includes/class-ajax.php','includes/class-common.php','assets/js/common.js','includes/class-shortcodes.php','includes/shortcodes/form-elements.php']
    harnessfiles=[Path(__file__),Path(__file__).with_name('validation-compatibility-submit.php'),Path(__file__).with_name('validation-compatibility-cases.json'),Path(a.bootstrap)]
    receipt=dict(status=status,plugin=str(plugin),group=a.group,php=php,
        source_hashes={f:hashlib.sha256((plugin/f).read_bytes()).hexdigest() for f in sourcefiles},
        harness_hashes={str(f):hashlib.sha256(f.read_bytes()).hexdigest() for f in harnessfiles},
        cases=results,passed=sum(x['ok'] for x in results),total=len(results),planned=len(cases))
    evidence_stream.seek(0); evidence_stream.write(json.dumps(receipt,indent=2)); evidence_stream.truncate(); evidence_stream.flush()
    return receipt
def diagnostic_text(output):
    # TimeoutExpired can contain bytes even with text=True, including a partial
    # UTF-8 sequence when the process was stopped mid-write.
    return output.decode('utf-8',errors='replace') if isinstance(output,bytes) else output or ''

persist('running')
for c in cases:
    try:
        r=subprocess.run(php_command+[str(Path(__file__).with_name('validation-compatibility-submit.php'))],input=json.dumps(c),text=True,encoding='utf-8',env=env,cwd=plugin,capture_output=True,timeout=30)
    except (OSError,subprocess.TimeoutExpired) as error:
        results.append(dict(case=c,ok=False,execution_error=str(error),
            stdout=diagnostic_text(getattr(error,'stdout',None)),
            stderr=diagnostic_text(getattr(error,'stderr',None))))
        persist('running'); print('FAIL',c['id'],str(error)); continue
    accepted='CHECKS_ACCEPTED:' in r.stdout
    data=json.loads(r.stdout.split('CHECKS_ACCEPTED:')[-1]) if accepted else {}
    # Beta's inherited informational event log is retained, not treated as a warning.
    unexpected_stderr=[line for line in r.stderr.splitlines() if line!='triggerEvent(sf.before.submission)']
    ok=accepted==c['accepted'] and r.returncode==0 and not unexpected_stderr
    if ok and accepted and 'payload' in c:
        import re
        for name in c.get('compare',[k for k,v in c['payload'].items() if isinstance(v,dict) and v.get('type')=='var']):
            got=(data.get(name) or {}).get('value')
            if name in c.get('field_patterns',{}):ok=ok and isinstance(got,str) and re.fullmatch(c['field_patterns'][name],got) is not None
            else:ok=ok and got==c['payload'][name]['value']
    elif ok and accepted and 'code_pattern' not in c and 'operation' not in c:ok=data.get('value')==c['value']
    if ok and not accepted:
        try: rejection=json.loads(r.stdout)
        except ValueError: rejection={}
        ok=rejection.get('error') is True and rejection.get('msg')==c.get('expected_msg','Invalid form data.')
    if ok and accepted and 'timestamp' in c:ok=data.get('timestamp')==c['timestamp']
    if ok and accepted and c.get('operation')=='code-claim':ok=data.get('first_claim') is True and data.get('duplicate_claim') is False and data.get('claim_cleaned') is True
    if ok and accepted and 'code_pattern' in c:
        import re
        ok=re.fullmatch(c['code_pattern'],data.get('value','')) is not None
    results.append(dict(case=c,ok=ok,exit=r.returncode,data=data,stdout=r.stdout,stderr=r.stderr))
    persist('running')
    print(('PASS' if ok else 'FAIL'),c['id'], 'accepted='+str(accepted),'' if ok else r.stdout[:250]+' '+r.stderr[:250])
receipt=persist('complete')
evidence_stream.close()
print(f"{receipt['passed']}/{receipt['total']} passed; receipt {out}")
sys.exit(0 if all(x['ok'] for x in results) else 1)
