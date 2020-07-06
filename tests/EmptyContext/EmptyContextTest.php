<?php

namespace NixEnterprise\JaegerClientTests\EmptyContext;

use NixEnterprise\JaegerClient\Context\EmptyContext;
use NixEnterprise\JaegerClientTests\TestCase;

/**
 * Class EmptyContextTest
 * @package NixEnterprise\JaegerClientTests\EmptyContext
 */
class EmptyContextTest extends TestCase
{

    /**
     * @var EmptyContext
     */
    protected $emptyContext;

    public function setUp(): void
    {
        parent::setUp();
        $this->emptyContext = new EmptyContext();
    }

    /**
     * @test
     */
    public function finishCallableWithoutPreparations()
    {
        $this->emptyContext->finish();
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function setPrivateTagsCallableWithoutPreparations()
    {
        $this->emptyContext->setPrivateTags([
            'a' => 'b'
        ]);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function setPropagatedTagsCallableWithoutPreparations()
    {
        $this->emptyContext->setPropagatedTags([
            'a' => 'b'
        ]);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function logCallableWithoutPreparations()
    {
        $this->emptyContext->log([
            'a' => 'b'
        ]);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function injectCallableWithoutPreparations()
    {
        $data = [];
        $this->emptyContext->inject($data);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function parseCallableWithoutPreparations()
    {
        $this->emptyContext->parse('', []);
        $this->addToAssertionCount(1);
    }

}
