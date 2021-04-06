<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage\Parser;

/**
 * Value object representing a FileSystem URI.
 */
interface UriInterface extends \Stringable
{
    /**
     * Retrieve the filesystem component of the URI.
     *
     * Please note that, unlike the RFC3986#3.1 specification, the filesystem
     * component (that is, the schema URI component) cannot be normalized to
     * lowercase and must be presented "as is".
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The filesystem name.
     */
    public function getFileSystem(): string;

    /**
     * Retrieve the filesystem's path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC7230#2.7.3. But this method MUST NOT automatically do this
     * normalization because in contexts with a trimmed base path, e.g. the
     * front controller, this difference becomes significant. It's the task of
     * the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer
     * to RFC3986#2 and RFC3986#3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath(): string;
}
