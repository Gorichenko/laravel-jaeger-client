<?php

namespace NixEnterprise\JaegerClientTests\SpanContext;

use NixEnterprise\JaegerClient\Context\Exceptions\NoSpanException;
use NixEnterprise\JaegerClient\Context\Exceptions\NoTracerException;
use NixEnterprise\JaegerClient\Context\SpanContext;
use NixEnterprise\JaegerClient\Context\TracerBuilder\TracerBuilder;
use NixEnterprise\JaegerClient\LogCleaner\LogCleaner;
use NixEnterprise\JaegerClient\SpanExtractor\SpanExtractor;
use NixEnterprise\JaegerClient\TagPropagator\TagPropagator;
use NixEnterprise\JaegerClientTests\TestCase;
use Jaeger\Jaeger;
use Jaeger\Span;
use Mockery;
use Mockery\MockInterface;
use const OpenTracing\Formats\TEXT_MAP;

/**
 * Class SpanContextTest
 * @package NixEnterprise\JaegerClientTests\SpanContext
 */
class SpanContextTest extends TestCase
{

    /**
     * @var SpanContext
     */
    protected $context;

    /**
     * @var TracerBuilder|MockInterface
     */
    protected $tracerBuilder;

    /**
     * @var SpanExtractor|MockInterface
     */
    protected $spanExtractor;

    /**
     * @var \OpenTracing\SpanContext|Mockery
     */
    protected $spanContext;

    /**
     * @var Jaeger|MockInterface
     */
    protected $tracer;

    /**
     * @var \OpenTracing\Span|MockInterface
     */
    protected $span;

    /**
     * @var LogCleaner
     */
    protected $logCleaner;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildMocks();

        $this->logCleaner = new LogCleaner();
        $this->context = new SpanContext(new TagPropagator(), new SpanExtractor(), $this->tracerBuilder, $this->logCleaner);
    }

    /**
     * @test
     */
    public function startBuildsTracer()
    {
        $this->assertTracerIsBuilt();
        $this->context->start();
    }

    /**
     * @test
     */
    public function finish()
    {
        $this->assertSpanIsFinished();
        $this->assertDataIsFlushedToTracer();
        $this->setUpContext();
        $this->context->finish();
    }

    /**
     * @test
     */
    public function injectAddsPropagatedTags()
    {
        $this->setUpContext();
        $this->context->setPropagatedTags([
            'tag1' => 'value1',
            'tag2' => 'value2',
        ]);

        $data = [];
        $this->context->inject($data);
        $this->assertArrayHasKey('propagated-tags', $data);
        $this->assertArrayHasKey('tag1', $data['propagated-tags']);
        $this->assertArrayHasKey('tag2', $data['propagated-tags']);
        $this->assertEquals('value1', $data['propagated-tags']['tag1']);
        $this->assertEquals('value2', $data['propagated-tags']['tag2']);
    }

    /**
     * @test
     */
    public function injectAddsSpanInject()
    {
        $data = [];
        $this->setUpContext();
        $this->tracer->shouldReceive('inject')->once()->with($this->spanContext, TEXT_MAP, $data);
        $this->context->inject($data);
    }

    /**
     * @test
     */
    public function parseExtractsPropagatedTags()
    {
        $data = [
            'propagated-tags' => [
                'tag1' => 'value1',
                'tag2' => 'value2',
            ]
        ];
        $this->useGenericTracer();

        $this->context->start();
        $this->span->shouldReceive('setTags')->with([
            'tag1' => 'value1',
            'tag2' => 'value2',
        ])->once();
        $this->context->parse('', $data);
    }

    /**
     * @test
     */
    public function parseExtractsSpanContext()
    {
        $data = ['test'];
        $this->useGenericTracer();
        $this->tracer->shouldReceive('extract')->with(TEXT_MAP, $data)->once();
        $this->context->start();
        $this->context->parse('', $data);
    }

    /**
     * @test
     */
    public function parseWithoutStartThrowsNoTracerException()
    {
        $this->expectException(NoTracerException::class);
        $this->context->parse('', []);
    }

    /**
     * @test
     */
    public function injectWithoutStartThrowsNoTracerException()
    {
        $data = [];
        $this->expectException(NoTracerException::class);
        $this->context->inject($data);
    }

    /**
     * @test
     */
    public function setPropagatedTagsAddsTagsToSpan()
    {
        $this->setUpContext();
        $this->span->shouldReceive('setTags')->once()->with([
            'a' => 'b'
        ]);
        $this->context->setPropagatedTags([
            'a' => 'b'
        ]);
    }

    /**
     * @test
     */
    public function setPrivateTagsAddsTagsToSpan()
    {
        $this->setUpContext();
        $this->span->shouldReceive('setTags')->once()->with([
            'a' => 'b'
        ]);
        $this->context->setPropagatedTags([
            'a' => 'b'
        ]);
    }

    /**
     * @test
     */
    public function logLogsToSpan()
    {
        $this->setUpContext();
        $this->span->shouldReceive('log')->once()->with([
            'a' => 'b'
        ]);
        $this->context->log([
            'a' => 'b'
        ]);
    }

    /**
     * @test
     */
    public function injectWithoutParseThrowsNoSpanException()
    {
        $this->useGenericTracer();
        $this->context->start();
        $this->expectException(NoSpanException::class);
        $data = [];
        $this->context->inject($data);
    }

    /**
     * @test
     */
    public function cleansLogs()
    {
        $this->setUpContext();

        $fields = [
            'message' => '1234567890'
        ];
        $this->logCleaner->setMaxLength(5)->setCutoffIndicator('...');
        $this->span->shouldReceive('log')->once()->with([
            'message' => '12345...'
        ]);
        $this->context->log($fields);
    }

    private function buildMocks()
    {
        $this->spanExtractor = Mockery::mock(SpanExtractor::class);
        $this->spanExtractor->shouldIgnoreMissing($this->spanExtractor);
        $this->tracerBuilder = Mockery::mock(TracerBuilder::class);
        $this->tracer = Mockery::mock(Jaeger::class);
        $this->tracer->shouldIgnoreMissing($this->tracer);
        $this->span = Mockery::mock(Span::class);
        $this->span->shouldIgnoreMissing($this->span);
        $this->spanContext = Mockery::mock(\OpenTracing\SpanContext::class);
        $this->span->shouldReceive('getContext')->andReturn($this->spanContext);
        $this->spanExtractor->shouldReceive('getBuiltSpan')->andReturn($this->span);
        $this->tracer->shouldReceive('startSpan')->andReturn($this->span);
    }

    private function assertTracerIsBuilt()
    {
        $this->tracerBuilder->shouldReceive('build')->once();
    }

    private function assertSpanIsFinished()
    {
        $this->span->shouldReceive('finish')->once();
    }

    private function assertDataIsFlushedToTracer()
    {
        $this->tracer->shouldReceive('flush')->once();
    }

    private function setUpContext()
    {
        $this->useGenericTracer();
        $this->context->start();
        $this->context->parse('', []);
    }

    private function useGenericTracer()
    {
        $this->tracerBuilder->shouldReceive('build')->andReturn($this->tracer);
    }

    private function expectLogCleanerCalled(array $fields)
    {
    }
}
