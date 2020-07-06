<?php

namespace NixEnterprise\JaegerClient\Context;

/**
 * Interface Context
 * @package NixEnterprise\JaegerClientRabbitMQ\MessageContext
 */
interface Context
{
    function finish();

    function setPrivateTags(array $tags);

    function setPropagatedTags(array $tags);

    function log(array $fields);

    function inject(array &$messageData);

    function parse(string $name, array $data);

    function child($name) : Context;

}
