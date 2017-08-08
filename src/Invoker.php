<?php

namespace Rareloop\Router;

use Invoker\Invoker as DIInvoker;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class Invoker extends DIInvoker
{
    private $requestResolver;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct(null, $container);

        $resolver = $this->getParameterResolver();

        // Allow the invoker to resolve dependencies via Type Hinting
        $containerResolver = new TypeHintContainerResolver($container);
        $resolver->prependResolver($containerResolver);

        $this->requestResolver = new TypeHintRequestResolver();
        $resolver->prependResolver($this->requestResolver);
    }

    public function setRequest(ServerRequestInterface $request) : Invoker
    {
        $this->requestResolver->setRequest($request);

        return $this;
    }
}
