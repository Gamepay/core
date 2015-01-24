<?php namespace net\xp_framework\unittest\core;

use unittest\PrerequisitesNotMetError;
use unittest\AssertionFailedError;
use lang\Runtime;
use lang\System;
use lang\Process;
use io\streams\Streams;
use io\streams\MemoryOutputStream;

/**
 * TestCase for Process class
 *
 * @see   xp://lang.Process
 */
class ProcessTest extends \unittest\TestCase {

  /**
   * Skips tests if process execution has been disabled.
   */
  #[@beforeClass]
  public static function verifyProcessExecutionEnabled() {
    if (Process::$DISABLED) {
      throw new PrerequisitesNotMetError('Process execution disabled', null, ['enabled']);
    }
  }

  /**
   * Return executable name
   *
   * @return  string
   */
  private function executable() {
    return Runtime::getInstance()->getExecutable()->getFilename();
  }

  /**
   * Return executable arguments. Takes care of prepending `--php` for HHVM.
   *
   * @param   string... $args
   * @return  string[]
   */
  private function arguments() {
    $args= func_get_args();
    if (defined('HHVM_VERSION')) {
      array_unshift($args, '--php');
    }
    return $args;
  }

  /**
   * Test process status information methods
   *
   * @see      xp://lang.Process#getProcessId
   * @see      xp://lang.Process#getFilename
   * @see      xp://lang.Process#getCommandLine
   * @see      xp://lang.Process#exitValue
   */
  #[@test]
  public function information() {
    $p= new Process($this->executable(), $this->arguments('-v'));
    try {
      $this->assertEquals(-1, $p->exitValue(), 'Process should not have exited yet');
      $this->assertNotEquals(0, $p->getProcessId());
      $this->assertNotEquals('', $p->getFilename());
      $this->assertTrue((new \lang\types\String($p->getCommandLine()))->contains('-v'));
      $p->close();
    } catch (AssertionFailedError $e) {
      $p->close();    // Ensure process is closed
      throw $e;
    }
  }

  #[@test]
  public function newInstance() {
    $p= Runtime::getInstance()->getExecutable()->newInstance($this->arguments('-v'));
    $version= defined('HHVM_VERSION') ? 'HipHop VM '.HHVM_VERSION : 'PHP '.PHP_VERSION;
    $this->assertEquals($version, $p->out->read(strlen($version)));
    $p->close();
  }

  #[@test]
  public function exitValueReturnedFromClose() {
    $p= new Process($this->executable(), $this->arguments('-r', 'exit(0);'));
    $this->assertEquals(0, $p->close());
  }

  #[@test]
  public function nonZeroExitValueReturnedFromClose() {
    $p= new Process($this->executable(), $this->arguments('-r', 'exit(2);'));
    $this->assertEquals(2, $p->close());
  }

  #[@test]
  public function exitValue() {
    $p= new Process($this->executable(), $this->arguments('-r', 'exit(0);'));
    $p->close();
    $this->assertEquals(0, $p->exitValue());
  }

  #[@test]
  public function nonZeroExitValue() {
    $p= new Process($this->executable(), $this->arguments('-r', 'exit(2);'));
    $p->close();
    $this->assertEquals(2, $p->exitValue());
  }

  #[@test]
  public function stdIn() {
    $p= new Process($this->executable(), $this->arguments('-r', 'fprintf(STDOUT, fread(STDIN, 0xFF));'));
    $p->in->write('IN');
    $p->in->close();
    $out= $p->out->read();
    $p->close();
    $this->assertEquals('IN', $out);
  }

  #[@test]
  public function stdOut() {
    $p= new Process($this->executable(), $this->arguments('-r', 'fprintf(STDOUT, "OUT");'));
    $out= $p->out->read();
    $p->close();
    $this->assertEquals('OUT', $out);
  }

  #[@test]
  public function stdErr() {
    $p= new Process($this->executable(), $this->arguments('-r', 'fprintf(STDERR, "ERR");'));
    $err= $p->err->read();
    $p->close();
    $this->assertEquals('ERR', $err);
  }

  #[@test, @expect('io.IOException')]
  public function runningNonExistantFile() {
    new Process(':FILE_DOES_NOT_EXIST:');
  }

  #[@test, @expect('io.IOException')]
  public function runningDirectory() {
    new Process(System::tempDir());
  }

  #[@test, @expect('io.IOException')]
  public function runningEmpty() {
    new Process('');
  }

  #[@test, @expect('lang.IllegalStateException')]
  public function nonExistantProcessId() {
    Process::getProcessById(-1);
  }

  #[@test]
  public function getByProcessId() {
    $pid= getmypid();
    $p= Process::getProcessById($pid);
    $this->assertInstanceOf('lang.Process', $p);
    $this->assertEquals($pid, $p->getProcessId());
  }

  #[@test]
  public function doubleClose() {
    $p= new Process($this->executable(), $this->arguments('-r', 'exit(222);'));
    $this->assertEquals(222, $p->close());
    $this->assertEquals(222, $p->close());
  }

  #[@test, @expect(class= 'lang.IllegalStateException', withMessage= '/Cannot close not-owned/')]
  public function closingProcessByProcessId() {
    Process::getProcessById(getmypid())->close();
  }

  #[@test]
  public function hugeStdout() {
    $p= new Process($this->executable(), $this->arguments('-r', 'fputs(STDOUT, str_repeat("*", 65536));'));
    $out= '';
    while (!$p->out->eof()) {
      $out.= $p->out->read();
    }
    $p->close();
    $this->assertEquals(65536, strlen($out));
  }

  #[@test]
  public function hugeStderr() {
    $p= new Process($this->executable(), $this->arguments('-r', 'fputs(STDERR, str_repeat("*", 65536));'));
    $err= '';
    while (!$p->err->eof()) {
      $err.= $p->err->read();
    }
    $p->close();
    $this->assertEquals(65536, strlen($err));
  }
}
