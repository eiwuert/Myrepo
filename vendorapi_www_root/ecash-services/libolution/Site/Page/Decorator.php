<?php

/**
 * Provides the ability to observe pages through a decorator.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Site
 * @see Site_Page_Observable
 */
class Site_Page_Decorator implements Site_IRequestProcessor
{
	/**
	 * @var Site_IRequestProcessor
	 */
	protected $processor;

	/**
	 * @var array
	 */
	protected $on_request = array();

	/**
	 * @var array
	 */
	protected $on_response = array();

	/**
	 * Creates a new page decorator for the given request unknownprocessor.
	 *
	 * @param Site_IRequestProcessor $processor
	 */
	public function __construct(Site_IRequestProcessor $processor)
	{
		$this->processor = $processor;
		$this->attachTraitHelpers();
	}

	/**
	 * Processes the observed request.
	 *
	 * This implementation delegates the request to the decorated request
	 * processor.
	 *
	 * @param Site_Request $request
	 * @return mixed
	 */
	protected function processObservedRequest(Site_Request $request)
	{
		return $this->processor->processRequest($request);
	}

	/**
	 * Add a page helper.
	 *
	 * @see Site_Page_IHelper
	 * @param Site_Page_IHelper $h
	 * @return NULL
	 */
	public function addHelper(Site_Page_IHelper $h)
	{
		if ($h instanceof Site_Page_IRequestHelper)
		{
			$this->on_request[] = $h;
		}

		if ($h instanceof Site_Page_IResponseHelper)
		{
			array_unshift($this->on_response, $h);
		}
	}

	/**
	 * Provides request and response hooks and delegates processing to processObservedRequest().
	 *
	 * @param Site_Request $r
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request)
	{
		$response = $this->onRequest($request);

		if (!$response)
		{
			$response = $this->processor->processRequest($request);

			if (!($response instanceof Site_IResponse))
			{
				throw new RuntimeException(get_class($this->processor) . '::processRequest() did not return a valid response object.');
			}
		}

		return $this->onResponse($request, $response);
	}

	/**
	 * Processes all request helpers.
	 *
	 * @param Site_Request $request
	 * @return mixed Site_IResponse or NULL
	 */
	protected function onRequest(Site_Request $request)
	{
		/* @var $h Site_Page_IRequestHelper */
		foreach ($this->on_request as $h)
		{
			$r = $h->onRequest($request);

			if ($r instanceof Site_Request)
			{
				$request = $r;
			}
			elseif ($r instanceof Site_IResponse)
			{
				return $r;
			}
		}

		return NULL;
	}

	/**
	 * Processes all response helpers.
	 *
	 * @param Site_Request $request
	 * @param Site_IResponse $response
	 * @return Site_IResponse
	 */
	protected function onResponse(Site_Request $request, Site_IResponse $response)
	{
		/* @var $h Site_Page_IResponseHelper */
		foreach ($this->on_response as $h)
		{
			if (($r = $h->onResponse($request, $response)) instanceof Site_IResponse)
			{
				$response = $r;
			}
		}
		return $response;
	}

	/**
	 * Attach all trait helpers based on the interfaces implemented by the
	 * decorated page.
	 */
	protected function attachTraitHelpers()
	{
		$class = new ReflectionClass($this->processor);
		$interfaces = $class->getInterfaces();
		$ignore = array();

		foreach ($interfaces as $name=>$i)
		{
			foreach ($i->getInterfaces() as $parent=>$n)
			{
				$ignore[] = $parent;
			}
		}

		foreach ($interfaces as $name=>$interface)
		{
			/* @var $interface ReflectionClass */
			if ($interface->implementsInterface('Site_Page_ITrait')
				&& !in_array($name, $ignore))
			{
				$helper = $this->findHelper($name);
				$this->addHelper($helper);
			}
		}
	}

	/**
	 * Returns the name of a helper class based on the passed in Trait
	 * interface.
	 *
	 * @param string $trait_name
	 * @return string
	 */
	protected function findHelper($trait_name)
	{
		$trait_pos = strrpos($trait_name, '_Traits_');
		if ($trait_pos === FALSE)
		{
			throw new InvalidArgumentException("The interface must be in a 'Traits' namespace: [{$trait_name}]");
		}
		$class_name = substr_replace($trait_name, '_Helpers_', $trait_pos, 8);
		return new $class_name($this->processor);
	}
}

?>
