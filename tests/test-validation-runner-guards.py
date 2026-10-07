"""Unit-only subprocess boundary probes. Never execute PHP or touch a WordPress DB."""
import contextlib,io,json,os,runpy,subprocess,sys,tempfile,unittest
from pathlib import Path
from unittest.mock import patch

class RunnerGuardTests(unittest.TestCase):
    def invoke(self,out,child):
        source=Path(os.environ.get('SF_RUNNER_SOURCE',Path(__file__).with_name('run-validation-compatibility.py')))
        plugin=Path(__file__).resolve().parent.parent
        if (plugin/'src/super-forms.php').exists():plugin=plugin/'src'
        args=[str(source),'--plugin',str(plugin),'--bootstrap',str(Path(__file__)),'--group','hotfix','--out',str(out)]
        with patch.object(sys,'argv',args),patch('subprocess.run',side_effect=child) as mock,contextlib.redirect_stdout(io.StringIO()):
            with self.assertRaises(SystemExit) as error:runpy.run_path(str(source),run_name='__main__')
        return error.exception,mock

    def test_reused_output_refuses_before_any_child_and_preserves_original(self):
        with tempfile.TemporaryDirectory(prefix='synthetic-runner-guard-',dir=os.environ.get('TMPDIR')) as directory:
            out=Path(directory)/'existing.json';out.write_text('original fixture bytes',encoding='utf-8')
            error,mock=self.invoke(out,AssertionError('PHP must not be invoked for an existing output'))
            self.assertIn('Refuse to overwrite',str(error));self.assertEqual(mock.call_count,0)
            self.assertEqual(out.read_text(encoding='utf-8'),'original fixture bytes')

    def test_simulated_child_timeouts_retain_failed_diagnostics(self):
        with tempfile.TemporaryDirectory(prefix='synthetic-runner-timeout-',dir=os.environ.get('TMPDIR')) as directory:
            out=Path(directory)/'unit-only-timeout-fixture.json'
            error,mock=self.invoke(out,subprocess.TimeoutExpired('synthetic-only-never-executed-child',30))
            self.assertEqual(error.code,1)
            receipt=json.loads(out.read_text(encoding='utf-8'))
            self.assertEqual(receipt['total'],mock.call_count)
            self.assertEqual(receipt['passed'],0)
            self.assertTrue(all('execution_error' in c for c in receipt['cases']))
            self.assertIn('harness_hashes',receipt)

if __name__=='__main__':unittest.main()
