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
 * Class SpanContext
 * @package NixEnterprise\JaegerClient\Context
 */
class SpanContext implements Context
{

    /**
     * @var Tracer
     */
    protected $tracer;

    /**
     * @var Span
     */
    protected $messageSpan;

    /**
     * @var UuidInterface
     */
    protected $uuid;

    /**
     * @var TagPropagator
     */
    protected $tagPropagator;

    /**
     * @var SpanExtractor
     */
    protected $spanExtractor;

    /**
     * @var TracerBuilder
     */
    protected $tracerBuilder;

    /**
     * @var LogCleaner
     */
    protected $logCleaner;

    /**
     * @var ContextArrayConverter\ContextArrayConverter
     */
    protected $arrayConverter;

    /**
     * MessageContext constructor.
     * @param TagPropagator $tagPropagator
     * @param SpanExtractor $spanExtractor
     * @param TracerBuilder $tracerBuilder
     * @param LogCleaner $logCleaner
     */
    public function __construct(
        TagPropagator $tagPropagator,
        SpanExtractor $spanExtractor,
        TracerBuilder $tracerBuilder,
        LogCleaner $logCleaner,
        ContextArrayConverter\ContextArrayConverter $arrayConverter
    )
    {
        $this->tagPropagator = $tagPropagator;
        $this->spanExtractor = $spanExtractor;
        $this->tracerBuilder = $tracerBuilder;
        $this->logCleaner = $logCleaner;
        $this->arrayConverter = $arrayConverter;
    }

    public function start()
    {
    }

    public function finish()
    {
        $this->tracer->finish($this->messageSpan);
    }

    /**
     * @param string $name
     * @param array $data
     */
    public function parse(string $name, array $data)
    {
        $this->assertHasTracer();

        $this->messageSpan = $this->spanExtractor
            ->setName($name)
            ->setData($data)
            ->setTracer($this->tracer)
            ->setTagPropagator($this->tagPropagator)
            ->extract()
            ->getBuiltSpan();

        // Set the uuid as a tag for this trace
        $this->uuid = Uuid::uuid1();
        $this->setPrivateTags([
            'uuid' => (string)$this->uuid,
            'environment' => config('app.env')
        ]);
        $this->tagPropagator->apply($this->messageSpan);
    }

    /**
     * @param array $tags
     */
    public function setPrivateTags(array $tags)
    {
        foreach ($tags as $name => $value)
            $this->messageSpan->addTag(new StringTag($name, $value));
    }

    /**
     * @param array $tags
     */
    public function setPropagatedTags(array $tags)
    {
        $this->tagPropagator->addTags($tags);
        $this->setPrivateTags($tags);
    }

    /**
     * @param array $messageData
     */
    public function inject(array &$messageData)
    {
        $this->assertHasTracer();
        $this->assertHasSpan();

        $context = $this->messageSpan->getContext();
        $this->arrayConverter->setContext($context)->inject($messageData);

        $this->tagPropagator->inject($messageData);
    }

    /**
     * @param array $fields
     */
    public function log(array $fields)
    {
        $this->logCleaner->setLogs($fields)->clean();
        foreach ($this->logCleaner->getLogs() as $logKey => $logValue) {
            if (!is_string($logValue))
                $logValue = json_encode($logValue);

            $this->messageSpan->addLog(new UserLog($logKey, 'info', $logValue));
        }
    }

    private function assertHasTracer()
    {
        if ($this->tracer instanceof Tracer) {
            return;
        }

        throw new NoTracerException();
    }

    private function assertHasSpan()
    {
        if ($this->messageSpan instanceof SpanInterface) {
            return;
        }

        throw new NoSpanException();
    }

    /**
     * @param $name
     * @return Context
     */
    public function child($name): Context
    {
        $context = app(SpanContext::class);
        $context->tracer = $this->tracer;
        $context->messageSpan = $this->tracer->start($name, [], $this->messageSpan->getContext());
        app()->instance('current-context', $context);
        // TODO: return wrapper
        return new WrapperContext($context, $this);
    }

}
