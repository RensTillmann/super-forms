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

    def test_timeout_retains_captured_byte_and_text_output(self):
        for stdout,stderr,expected_stdout,expected_stderr in [
                (b'SYNTHETIC_PARTIAL_STDOUT',b'SYNTHETIC_PARTIAL_STDERR','SYNTHETIC_PARTIAL_STDOUT','SYNTHETIC_PARTIAL_STDERR'),
                ('SYNTHETIC_TEXT_STDOUT','SYNTHETIC_TEXT_STDERR','SYNTHETIC_TEXT_STDOUT','SYNTHETIC_TEXT_STDERR'),
                (None,None,'',''),
                (b'SYNTHETIC_INCOMPLETE_UTF8_\xe2',b'SYNTHETIC_STDERR_\xff','SYNTHETIC_INCOMPLETE_UTF8_\ufffd','SYNTHETIC_STDERR_\ufffd')]:
            with self.subTest(stdout=stdout,stderr=stderr),tempfile.TemporaryDirectory(
                    prefix='synthetic-runner-output-',dir=os.environ.get('TMPDIR')) as directory:
                out=Path(directory)/'unit-only-output-fixture.json'
                error,mock=self.invoke(out,subprocess.TimeoutExpired(
                    'synthetic-only-never-executed-child',30,output=stdout,stderr=stderr))
                self.assertEqual(error.code,1)
                receipt=json.loads(out.read_text(encoding='utf-8'))
                self.assertEqual(receipt['total'],mock.call_count)
                self.assertEqual(receipt['passed'],0)
                for row in receipt['cases']:
                    self.assertFalse(row['ok'])
                    self.assertIn('execution_error',row)
                    self.assertEqual(row['stdout'],expected_stdout)
                    self.assertEqual(row['stderr'],expected_stderr)
                self.assertIn('source_hashes',receipt)
                self.assertIn('harness_hashes',receipt)

    def test_child_launch_failure_retains_failure_receipt(self):
        with tempfile.TemporaryDirectory(prefix='synthetic-runner-launch-',dir=os.environ.get('TMPDIR')) as directory:
            out=Path(directory)/'unit-only-launch-fixture.json'
            error,mock=self.invoke(out,OSError('synthetic-only-child-launch-failure'))
            self.assertEqual(error.code,1)
            receipt=json.loads(out.read_text(encoding='utf-8'))
            self.assertEqual(receipt['total'],mock.call_count)
            self.assertEqual(receipt['passed'],0)
            for row in receipt['cases']:
                self.assertFalse(row['ok'])
                self.assertEqual(row['execution_error'],'synthetic-only-child-launch-failure')
                self.assertEqual(row['stdout'],'')
                self.assertEqual(row['stderr'],'')

if __name__=='__main__':unittest.main()
