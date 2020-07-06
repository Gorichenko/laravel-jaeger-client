<?php

namespace NixEnterprise\JaegerClient\SpanExtractor;

use NixEnterprise\JaegerClient\Context\ContextArrayConverter\ContextArrayConverter;
use NixEnterprise\JaegerClient\TagPropagator\TagPropagator;
use Jaeger\Span\Context\SpanContext;
use Jaeger\Span\SpanInterface;
use Jaeger\Tracer\Tracer;

/**
 * Class SpanExtractor
 * @package NixEnterprise\JaegerClient\SpanExtractor
 */
class SpanExtractor
{

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var TagPropagator
     */
    protected $tagPropagator;

    /**
     * @var array
     */
    protected $traceContent;

    /**
     * @var Tracer
     */
    protected $tracer;

    /**
     * @var SpanInterface
     */
    protected $builtSpan;

    /**
     * @var SpanContext
     */
    private $spanContext;

    /**
     * @var ContextArrayConverter
     */
    protected $converter;

    /**
     * SpanExtractor constructor.
     * @param ContextArrayConverter $converter
     */
    public function __construct(ContextArrayConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @return $this
     */
    public function extract()
    {
        $this->parseContext();

        // Start the global span, it'll wrap the request/console lifecycle
        $this->builtSpan = $this->tracer->start($this->name, [], $this->spanContext);
        $this->tracer->finish($this->builtSpan);
        return $this;
    }

    private function parseContext()
    {
        $this->resetContext();
        $this->extractContextFromData();
    }

    private function resetContext()
    {
        $this->spanContext = null;
        $this->tagPropagator->reset();
    }

    private function extractContextFromData()
    {
        $this->traceContent = $this->data;
        $this->extractSpanContext();
        $this->extractPropagatedTags();
    }

    private function extractSpanContext()
    {
        $this->converter->extract($this->traceContent);
        $this->spanContext = $this->converter->getContext();
    }

    private function extractPropagatedTags()
    {
        $this->tagPropagator->extract($this->traceContent);
    }

    /**
     * @param string $name
     * @return SpanExtractor
     */
    public function setName(string $name): SpanExtractor
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param array $data
     * @return SpanExtractor
     */
    public function setData(array $data): SpanExtractor
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param TagPropagator $tagPropagator
     * @return SpanExtractor
     */
    public function setTagPropagator(TagPropagator $tagPropagator): SpanExtractor
    {
        $this->tagPropagator = $tagPropagator;
        return $this;
    }

    /**
     * @param Tracer $tracer
     * @return $this
     */
    public function setTracer(Tracer $tracer)
    {
        $this->tracer = $tracer;
        return $this;
    }

    /**
     * @return SpanInterface
     */
    public function getBuiltSpan(): SpanInterface
    {
        return $this->builtSpan;
    }

}
