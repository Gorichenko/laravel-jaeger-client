<?php

namespace NixEnterprise\JaegerClient\Context\TracerBuilder;

use Jaeger\Tracer\Tracer;

/**
 * Class TracerBuilder
 * @package NixEnterprise\JaegerClient\Context\TracerBuilder
 */
class TracerBuilder {

	public function build() {
	    return app(Tracer::class);
	}

}
