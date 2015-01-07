<?php namespace net\xp_framework\unittest\io\streams;

use lang\types\Bytes;
use io\streams\MemoryInputStream;

/**
 * Abstract base class for all compressing output stream tests
 *
 */
abstract class AbstractDecompressingInputStreamTest extends \unittest\TestCase {

  /**
   * Get filter we depend on
   *
   * @return  string
   */
  protected abstract function filter();

  /**
   * Get stream
   *
   * @param   io.streams.InputStream wrapped
   * @return  io.streams.InputStream
   */
  protected abstract function newStream(\io\streams\InputStream $wrapped);

  /**
   * Compress data
   *
   * @param   string in
   * @return  int level
   * @return  string
   */
  protected abstract function compress($in, $level);

  /**
   * Setup method. Ensure filter we depend on is available
   */
  public function setUp() {
    $depend= $this->filter();
    if (!in_array($depend, stream_get_filters())) {
      throw new \unittest\PrerequisitesNotMetError(ucfirst($depend).' stream filter not available', null, [$depend]);
    }
  }

  /**
   * Test single read
   *
   */
  #[@test]
  public function singleRead() {
    $in= new MemoryInputStream($this->compress('Hello', 6));
    $decompressor= $this->newStream($in);
    $chunk= $decompressor->read();
    $decompressor->close();
    $this->assertEquals('Hello', $chunk);
  }

  /**
   * Test multiple consecutive reads
   *
   */
  #[@test]
  public function multipleReads() {
    $in= new MemoryInputStream($this->compress('Hello World', 6));
    $decompressor= $this->newStream($in);
    $chunk1= $decompressor->read(5);
    $chunk2= $decompressor->read(1);
    $chunk3= $decompressor->read(5);
    $decompressor->close();
    $this->assertEquals('Hello', $chunk1);
    $this->assertEquals(' ', $chunk2);
    $this->assertEquals('World', $chunk3);
  }

  /**
   * Test highest level of compression (9)
   *
   */
  #[@test]
  public function highestLevel() {
    $in= new MemoryInputStream($this->compress('Hello', 9));
    $decompressor= $this->newStream($in);
    $chunk= $decompressor->read();
    $decompressor->close();
    $this->assertEquals('Hello', $chunk);
  }

  /**
   * Test highest level of compression (1)
   *
   */
  #[@test]
  public function lowestLevel() {
    $in= new MemoryInputStream($this->compress('Hello', 1));
    $decompressor= $this->newStream($in);
    $chunk= $decompressor->read();
    $decompressor->close();
    $this->assertEquals('Hello', $chunk);
  }

  /**
   * Test closing a stream right after creation
   *
   */
  #[@test]
  public function closingRightAfterCreation() {
    $decompressor= $this->newStream(new MemoryInputStream($this->compress('Hello', 1)));
    $decompressor->close();
  }

  /**
   * Test closing a stream twice has no effect.
   *
   * @see   xp://lang.Closeable#close
   */
  #[@test]
  public function closingTwice() {
    $decompressor= $this->newStream(new MemoryInputStream($this->compress('Hello', 1)));
    $decompressor->close();
    $decompressor->close();
  }
}
