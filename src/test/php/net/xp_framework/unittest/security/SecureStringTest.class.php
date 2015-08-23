<?php namespace net\xp_framework\unittest\security;

use security\SecureString;
use security\SecurityException;
use lang\IllegalStateException;
use lang\IllegalArgumentException;
use lang\XPException;
use lang\Throwable;

/**
 * Baseclass for test cases for security.SecureString
 */
abstract class SecureStringTest extends \unittest\TestCase {

  /**
   * Retrieve value
   *
   * @return string
   */
  protected function getValue() { return 'payload'; }

  #[@test]
  public function create() {
    new SecureString('payload');
  }

  #[@test]
  public function create_from_function_return_value() {
    new SecureString($this->getValue());
  }

  #[@test, @expect(IllegalStateException::class)]
  public function not_serializable() {
    serialize(new SecureString('payload'));
  }

  #[@test]
  public function var_export_not_revealing_payload() {
    $export= var_export(new SecureString('payload'), true);
    $this->assertFalse(strpos($export, 'payload'));
  }

  #[@test]
  public function var_dump_not_revealing_payload() {
    ob_start();
    var_dump(new SecureString('payload'));

    $output= ob_get_contents();
    ob_end_clean();

    $this->assertFalse(strpos($output, 'payload'));
  }

  #[@test]
  public function toString_not_revealing_payload() {
    $output= (new SecureString('payload'))->toString();
    $this->assertFalse(strpos($output, 'payload'));
  }

  #[@test]
  public function string_cast_not_revealing_payload() {
    $output= (string)new SecureString('payload');
    $this->assertFalse(strpos($output, 'payload'));
  }

  #[@test]
  public function array_cast_not_revealing_payload() {
    $output= var_export((array)new SecureString('payload'), 1);
    $this->assertFalse(strpos($output, 'payload'));
  }

  #[@test]
  public function getPayload_reveals_original_data() {
    $secure= new SecureString('payload');
    $this->assertEquals('payload', $secure->getCharacters());
  }

  #[@test]
  public function big_data() {
    $data= str_repeat('*', 1024000);
    $secure= new SecureString($data);
    $this->assertEquals($data, $secure->getCharacters());
  }

  #[@test]
  public function creation_never_throws_exception() {
    $called= false;
    SecureString::setBacking(function($value) use (&$called) {
      $called= true;
      throw new XPException('Something went wrong - intentionally.');
    }, function($value) { return null; });

    new SecureString('foo');
    $this->assertTrue($called);
  }

  #[@test, @expect(class= SecurityException::class, withMessage= '/An error occurred during storing the encrypted password./')]
  public function decryption_throws_exception_if_creation_has_failed() {
    $called= false;
    SecureString::setBacking(function($value) {
      throw new XPException('Something went wrong - intentionally.');
    }, function($value) { return null; });

    // Creation may never throw exception
    try {
      $s= new SecureString('foo');
    } catch (\Throwable $t) {
      $this->fail('Exception thrown where no exception may be thrown', $t, null);
    }

    // Buf if creation failed, an exception must be raised here:
    $s->getCharacters();
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function useBacking_with_invalid_backing_throws_exception() {
    SecureString::useBacking(77);
  }
}
