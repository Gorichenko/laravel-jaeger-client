<?php

namespace NixEnterprise\JaegerClient\Context;

use NixEnterprise\JaegerClient\Context\Exceptions\NoSpanException;
use NixEnterprise\JaegerClient\Context\Exceptions\NoTracerException;
use NixEnterprise\JaegerClient\Context\TracerBuilder\TracerBuilder;
use NixEnterprise\JaegerClient\LogCleaner\LogCleaner;
use NixEnterprise\JaegerClient\SpanExtractor\SpanExtractor;
use NixEnterprise\JaegerClient\TagPropagator\TagPropagator;
use Jaeger\Log\UserLog;
use Jaeger\Span\Span;
use Jaeger\Span\SpanInterface;
use Jaeger\Tag\StringTag;
use Jaeger\Tracer\Tracer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class MasterSpanContext
 * @package NixEnterprise\JaegerClient\Context
 */
class MasterSpanContext extends SpanContext implements Context
{

    public function start()
    {
        $this->buildTracer();
    }

    public function finish()
    {
        $this->tracer->flush();
    }

    protected function buildTracer(): void
    {
        $this->tracer = $this->tracerBuilder->build();
    }

}
