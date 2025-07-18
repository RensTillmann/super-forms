<?php

namespace Stripe;

/**
 * Class Collection.
 *
 * @template TStripeObject of StripeObject
 * @template-implements \IteratorAggregate<TStripeObject>
 *
 * @property string $object
 * @property string $url
 * @property bool $has_more
 * @property TStripeObject[] $data
 */
class Collection extends StripeObject implements \Countable, \IteratorAggregate {

	const OBJECT_NAME = 'list';

	use ApiOperations\Request;

	/** @var array */
	protected $filters = array();

	/**
	 * @return string the base URL for the given class
	 */
	public static function baseUrl() {
		return Stripe::$apiBase;
	}

	/**
	 * Returns the filters.
	 *
	 * @return array the filters
	 */
	public function getFilters() {
		return $this->filters;
	}

	/**
	 * Sets the filters, removing paging options.
	 *
	 * @param array $filters the filters
	 */
	public function setFilters( $filters ) {
		$this->filters = $filters;
	}

	/**
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $k ) {
		if ( \is_string( $k ) ) {
			return parent::offsetGet( $k );
		}
		$msg = "You tried to access the {$k} index, but Collection " .
					'types only support string keys. (HINT: List calls ' .
					'return an object with a `data` (which is the data ' .
					"array). You likely want to call ->data[{$k}])";

		throw new Exception\InvalidArgumentException( $msg );
	}

	/**
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @throws Exception\ApiErrorException
	 *
	 * @return Collection<TStripeObject>
	 */
	public function all( $params = null, $opts = null ) {
		self::_validateParams( $params );
		list($url, $params) = $this->extractPathAndUpdateParams( $params );

		list($response, $opts) = $this->_request( 'get', $url, $params, $opts );
		$obj                   = Util\Util::convertToStripeObject( $response, $opts );
		if ( ! ( $obj instanceof \Stripe\Collection ) ) {
			throw new \Stripe\Exception\UnexpectedValueException(
				'Expected type ' . self::class . ', got "' . \get_class( $obj ) . '" instead.'
			);
		}
		$obj->setFilters( $params );

		return $obj;
	}

	/**
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @throws Exception\ApiErrorException
	 *
	 * @return TStripeObject
	 */
	public function create( $params = null, $opts = null ) {
		self::_validateParams( $params );
		list($url, $params) = $this->extractPathAndUpdateParams( $params );

		list($response, $opts) = $this->_request( 'post', $url, $params, $opts );

		return Util\Util::convertToStripeObject( $response, $opts );
	}

	/**
	 * @param string            $id
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @throws Exception\ApiErrorException
	 *
	 * @return TStripeObject
	 */
	public function retrieve( $id, $params = null, $opts = null ) {
		self::_validateParams( $params );
		list($url, $params) = $this->extractPathAndUpdateParams( $params );

		$id                    = Util\Util::utf8( $id );
		$extn                  = \urlencode( $id );
		list($response, $opts) = $this->_request(
			'get',
			"{$url}/{$extn}",
			$params,
			$opts
		);

		return Util\Util::convertToStripeObject( $response, $opts );
	}

	/**
	 * @return int the number of objects in the current page
	 */
	#[\ReturnTypeWillChange]
	public function count() {
		return \count( $this->data );
	}

	/**
	 * @return \ArrayIterator an iterator that can be used to iterate
	 *    across objects in the current page
	 */
	#[\ReturnTypeWillChange]
	public function getIterator() {
		return new \ArrayIterator( $this->data );
	}

	/**
	 * @return \ArrayIterator an iterator that can be used to iterate
	 *    backwards across objects in the current page
	 */
	public function getReverseIterator() {
		return new \ArrayIterator( \array_reverse( $this->data ) );
	}

	/**
	 * @return \Generator|TStripeObject[] A generator that can be used to
	 *    iterate across all objects across all pages. As page boundaries are
	 *    encountered, the next page will be fetched automatically for
	 *    continued iteration.
	 */
	public function autoPagingIterator() {
		$page = $this;

		while ( true ) {
			$filters = $this->filters ?: array();
			if ( \array_key_exists( 'ending_before', $filters )
				&& ! \array_key_exists( 'starting_after', $filters ) ) {
				foreach ( $page->getReverseIterator() as $item ) {
					yield $item;
				}
				$page = $page->previousPage();
			} else {
				foreach ( $page as $item ) {
					yield $item;
				}
				$page = $page->nextPage();
			}

			if ( $page->isEmpty() ) {
				break;
			}
		}
	}

	/**
	 * Returns an empty collection. This is returned from {@see nextPage()}
	 * when we know that there isn't a next page in order to replicate the
	 * behavior of the API when it attempts to return a page beyond the last.
	 *
	 * @param null|array|string $opts
	 *
	 * @return Collection
	 */
	public static function emptyCollection( $opts = null ) {
		return self::constructFrom( array( 'data' => array() ), $opts );
	}

	/**
	 * Returns true if the page object contains no element.
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return empty( $this->data );
	}

	/**
	 * Fetches the next page in the resource list (if there is one).
	 *
	 * This method will try to respect the limit of the current page. If none
	 * was given, the default limit will be fetched again.
	 *
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @return Collection<TStripeObject>
	 */
	public function nextPage( $params = null, $opts = null ) {
		if ( ! $this->has_more ) {
			return static::emptyCollection( $opts );
		}

		$lastId = \end( $this->data )->id;

		$params = \array_merge(
			$this->filters ?: array(),
			array( 'starting_after' => $lastId ),
			$params ?: array()
		);

		return $this->all( $params, $opts );
	}

	/**
	 * Fetches the previous page in the resource list (if there is one).
	 *
	 * This method will try to respect the limit of the current page. If none
	 * was given, the default limit will be fetched again.
	 *
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @return Collection<TStripeObject>
	 */
	public function previousPage( $params = null, $opts = null ) {
		if ( ! $this->has_more ) {
			return static::emptyCollection( $opts );
		}

		$firstId = $this->data[0]->id;

		$params = \array_merge(
			$this->filters ?: array(),
			array( 'ending_before' => $firstId ),
			$params ?: array()
		);

		return $this->all( $params, $opts );
	}

	/**
	 * Gets the first item from the current page. Returns `null` if the current page is empty.
	 *
	 * @return null|TStripeObject
	 */
	public function first() {
		return \count( $this->data ) > 0 ? $this->data[0] : null;
	}

	/**
	 * Gets the last item from the current page. Returns `null` if the current page is empty.
	 *
	 * @return null|TStripeObject
	 */
	public function last() {
		return \count( $this->data ) > 0 ? $this->data[ \count( $this->data ) - 1 ] : null;
	}

	private function extractPathAndUpdateParams( $params ) {
		$url = \parse_url( $this->url );
		if ( ! isset( $url['path'] ) ) {
			throw new Exception\UnexpectedValueException( "Could not parse list url into parts: {$url}" );
		}

		if ( isset( $url['query'] ) ) {
			// If the URL contains a query param, parse it out into $params so they
			// don't interact weirdly with each other.
			$query = array();
			\parse_str( $url['query'], $query );
			$params = \array_merge( $params ?: array(), $query );
		}

		return array( $url['path'], $params );
	}
}
