<?php

namespace NixEnterprise\JaegerClient\Context;

/**
 * Class WrapperContext
 * @package NixEnterprise\JaegerClient\Context
 */
class WrapperContext implements Context
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Context
     */
    protected $parentContext;

    /**
     * WrapperContext constructor.
     * @param Context $context
     * @param Context $parentContext
     */
    public function __construct(
        Context $context,
        Context $parentContext
    )
    {
        $this->context = $context;
        $this->parentContext = $parentContext;
    }

    public function finish()
    {
        $result = $this->context->finish();

        if (app('current-context') !== $this->context) {
            return;
        }

        app()->instance('current-context', $this->parentContext);

        return $result;
    }

    /**
     * @param array $tags
     * @return mixed
     */
    public function setPrivateTags(array $tags)
    {
        return $this->context->setPrivateTags($tags);
    }

    /**
     * @param array $tags
     * @return mixed
     */
    public function setPropagatedTags(array $tags)
    {
        return $this->context->setPropagatedTags($tags);
    }

    /**
     * @param array $fields
     * @return mixed
     */
    public function log(array $fields)
    {
        return $this->context->log($fields);
    }

    /**
     * @param array $messageData
     * @return mixed
     */
    public function inject(array &$messageData)
    {
        return $this->context->inject($messageData);
    }

    /**
     * @param string $name
     * @param array $data
     * @return mixed
     */
    public function parse(string $name, array $data)
    {
        return $this->context->parse($name, $data);
    }

    /**
     * @param $name
     * @return Context
     */
    public function child($name): Context
    {
        return $this->context->child($name);
    }

    public function __destruct()
    {
        $this->finish();
    }

}
