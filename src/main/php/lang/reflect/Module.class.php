<?php namespace lang\reflect;

use lang\IClassLoader;

/**
 * Represents a module
 *
 * @test  xp://net.xp_framework.unittest.reflection.ModuleTest
 */
class Module extends \lang\Object {
  public static $registered= [];

  /**
   * Creates a new module
   *
   * @param  string $name
   * @param  lang.IClassLoader $classLoader
   */
  public function __construct($name, IClassLoader $classLoader) {
    $this->name= $name;
    $this->classLoader= $classLoader;
  }

  /** @return string */
  public function name() { return $this->name; }

  /** @return lang.IClassLoader */
  public function classLoader() { return $this->classLoader; }

  /**
   * Initialize this module. Template method, override in subclasses!
   *
   * @return void
   */
  public function initialize() { }

  /**
   * Finalize this module. Template method, override in subclasses!
   *
   * @return void
   */
  public function finalize() { }

  /**
   * Returns whether a given value equals this module
   *
   * @param  var $cmp
   * @return bool
   */
  public function equals($cmp) {
    return $cmp instanceof self && $cmp->name === $this->name;
  }

  /**
   * Returns a string representation of this module
   *
   * @return string
   */
  public function toString() {
    return $this->getClassName().'<'.$this->name.'@'.$this->classLoader->toString().'>';
  }

  /**
   * Register a module
   *
   * @param  self $module
   * @return self
   */
  public static function register(self $module) {
    self::$registered[$module->name()]= $module;
    $module->initialize();
    return $module;
  }

  /**
   * Remove a registered module
   *
   * @param  self $module
   */
  public static function remove(self $module) {
    $module->finalize();
    unset(self::$registered[$module->name()]);
  }

  /**
   * Returns whether a module is registered by a given name
   *
   * @param  string $name
   * @return bool
   */
  public static function loaded($name) {
    return isset(self::$registered[$name]);
  }

  /**
   * Retrieve a previously registered module by its name
   * 
   * @param  string $name
   * @return self
   * @throws lang.ElementNotFoundException
   */
  public static function forName($name) {
    if (!isset(self::$registered[$name])) {
      raise('lang.ElementNotFoundException', 'No module "'.$name.'" declared');
    }
    return self::$registered[$name];
  }
}