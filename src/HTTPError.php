<?php

namespace Koded\Framework;

use Koded\Http\Interfaces\HttpStatus;
use Koded\Http\StatusCode;
use function Koded\Stdlib\{json_serialize, xml_serialize};


interface KodedHTTPError
{
    public function getStatusCode(): int;

    public function getTitle(): string;

    public function getType(): string;

    public function getDetail(): string;

    public function getInstance(): string;

    public function getHeaders(): iterable;

    public function setMember(string $name, mixed $value): static;

    public function toJson(): string;

    public function toXml(): string;

    public function toArray(): array;
}

/**
 * Represents a generic HTTP error.
 * Follows the RFC-7807 (https://tools.ietf.org/html/rfc7807)
 *
 * Raise an instance of subclass of `HTTPError` to have Koded return
 * a formatted error response and appropriate HTTP status code to
 * the client when something goes wrong. JSON and XML media types are
 * supported by default.
 *
 * NOTE:
 *  if you wish to return custom error messages, you can create
 *  your own HTTPError subclass and register it with the error
 *  handler method to convert it into the desired HTTP response.
 *
 * @link https://tools.ietf.org/html/rfc7807
 */
class HTTPError extends \RuntimeException implements KodedHTTPError
{
    /**
     * Extension members for problem type definitions may extend the
     * problem details object with additional information. Clients
     * consuming problem MUST ignore any extensions that they don't
     * recognize, allowing problem types to evolve and include
     * additional information in the future.
     *
     * @var array
     */
    protected array $members = [];

    /**
     * HTTPError constructor.
     *
     * @param int             $status   HTTP status code
     * @param string          $title    Error title to send to the client. If not provided, defaults to status line
     * @param string          $detail   Human-friendly description of the error, along with a helpful suggestion or two
     * @param string          $instance A URI reference that identifies the specific occurrence of the problem.
     * @param string          $type     A URI reference that identifies the problem type and points to a human-readable documentation
     * @param array|null      $headers  Extra headers to add to the response
     * @param \Throwable|null $previous The previous Throwable, if any
     */
    public function __construct(
        int $status,
        protected string $title = '',
        protected string $detail = '',
        protected string $instance = '',
        protected string $type = '',
        protected ?array $headers = [],
        ?\Throwable $previous = null)
    {
        $this->code = $status ?: HttpStatus::I_AM_TEAPOT;
        $this->message = $title ?: HttpStatus::CODE[$this->code];
        [
            'title'    => $this->title,
            'type'     => $this->type,
            'detail'   => $this->detail,
            'instance' => $this->instance
        ] = $this->toArray();
        parent::__construct($this->message, $this->code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getHeaders(): iterable
    {
        return $this->headers ?? [];
    }

    public function setMember(string $name, mixed $value): static
    {
        $this->members[$name] = $value;
        return $this;
    }

    public function toJson(): string
    {
        return \rawurldecode(json_serialize(\array_filter($this->toArray())));
    }

    public function toXml(): string
    {
        return \rawurldecode(xml_serialize('problem', \array_filter($this->toArray())));
    }

    /**
     * @return array{status: int, instance: string, detail: string, title: string, type: string}
     */
    public function toArray(): array
    {
        $status = ($this->code < 100 || $this->code > 599)
            ? HttpStatus::I_AM_TEAPOT
            : $this->code;

        return \array_merge([
            'status'   => $status,
            'instance' => $this->instance,
            'detail'   => $this->detail ?: StatusCode::description($status),
            'title'    => $this->title ?: $this->message,
            'type'     => $this->type ?: "https://httpstatuses.com/$status",
        ], $this->members);
    }

    public function __serialize(): array
    {
        return $this->toArray() + [
            'members' => $this->members,
            'headers' => $this->headers,
        ];
    }

    public function __unserialize(array $data): void
    {
        list(
            'status'   => $this->code,
            'title'    => $this->title,
            'title'    => $this->message,
            'type'     => $this->type,
            'detail'   => $this->detail,
            'instance' => $this->instance,
            'members'  => $this->members,
            'headers'  => $this->headers,
        ) = $data;
    }
}
