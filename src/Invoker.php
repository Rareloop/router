<?php

namespace Rareloop\Router;

use Invoker\Invoker as DIInvoker;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\ParameterResolver;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class Invoker extends DIInvoker
{
    private $requestResolver;

    public function __construct(ContainerInterface $container, ?ParameterResolver $parameterResolver = null)
    {
        parent::__construct($parameterResolver, $container);

        $resolver = $this->getParameterResolver();

        // Allow the invoker to resolve dependencies via Type Hinting
        $containerResolver = new TypeHintContainerResolver($container);
        $resolver->prependResolver($containerResolver);

        $this->requestResolver = new TypeHintRequestResolver();
        $resolver->prependResolver($this->requestResolver);
    }

    public function setRequest(ServerRequestInterface $request): Invoker
    {
        $this->requestResolver->setRequest($request);

        return $this;
    }
}
