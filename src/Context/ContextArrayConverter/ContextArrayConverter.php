<?php

namespace NixEnterprise\JaegerClient\Context\ContextArrayConverter;

use Illuminate\Support\Arr;
use Jaeger\Span\Context\SpanContext;

/**
 * Class ContextArrayConverter
 * @package NixEnterprise\JaegerClient\Context\ContextArrayConverter
 */
class ContextArrayConverter
{

    /**
     * @var SpanContext
     */
    protected $context;

    /**
     * @param $data
     * @return $this
     */
    public function extract($data)
    {
        $hasAny = (
            Arr::has($data, 'trace-id')
            || Arr::has($data, 'span-id')
            || Arr::has($data, 'parent-id')
            || Arr::has($data, 'flags')
            || Arr::has($data, 'baggage')
        );
        if(!$hasAny) {
            $this->context = null;
            return $this;
        }

        $this->context = new SpanContext(
            Arr::get($data, 'trace-id'),
            Arr::get($data, 'span-id'),
            Arr::get($data, 'parent-id'),
            Arr::get($data, 'flags'),
            Arr::get($data, 'baggage')
        );
        return $this;
    }

    /**
     * @return SpanContext
     */
    public function getContext(): ?SpanContext
    {
        return $this->context;
    }

    /**
     * @param SpanContext $context
     * @return ContextArrayConverter
     */
    public function setContext(SpanContext $context): ContextArrayConverter
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param $data
     */
    public function inject(&$data)
    {
        Arr::set($data, 'trace-id', $this->context->getTraceId());
        Arr::set($data, 'span-id', $this->context->getSpanId());
        Arr::set($data, 'parent-id', $this->context->getParentId());
        Arr::set($data, 'flags', $this->context->getFlags());
        Arr::set($data, 'baggage', $this->context->getBaggage());
    }
}
