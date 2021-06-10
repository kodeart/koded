<?php declare(strict_types=1);

const INVALID_VERSION_ARRAY = ['0.0.0', '0', '0'];
const SEMVER_REGEX = '/^([0-9]+\.[0-9]+\.[0-9]+)(?:\-([a-z0-9-]+(?:\.[a-z0-9-]+)*))?(?:\+([a-z0-9-]+(?:\.[a-z0-9-]+)*))?$/i';

/**
 * Returns the Koded version number from VERSION file.
 *
 * @param array $version
 * @return string SemVer-compliant version
 * @see http://semver.org/
 */
function get_version(array $version = []): string
{
    $v = get_complete_version($version);
    // Most common format (X.Y.Z)
    if (empty($v[1]) && empty($v[2])) {
        return $v[0];
    }
    // Special case when pre-release is ALPHA and build is empty
    if ('alpha' === \strtolower($v[1]) && empty($v[2])) {
        $v[2] = get_git_changeset();
        return \sprintf('%s-%s+%s', ...$v);
    }
    if (empty($v[2])) {
        return \sprintf('%s-%s', ...$v);
    }
    if (empty($v[1])) {
        $v[1] = $v[2];
        return \sprintf('%s+%s', ...$v);
    }
    return \sprintf('%s-%s+%s', ...$v);
}

/**
 * @internal
 *
 * Returns the version parts in array.
 *
 * @param string $version
 * @return array If version is not parsed by the semver rules, returns 0-filled array
 */
function get_version_array(string $version): array
{
    if (!\preg_match(SEMVER_REGEX, \trim($version), $match)) {
        return INVALID_VERSION_ARRAY;
    }
    \array_shift($match);
    $match = \array_replace(INVALID_VERSION_ARRAY, $match);
    $match = \array_map(fn($v) => empty($v) ? '0' : $v, $match);
    return $match;
}

/**
 * @internal
 *
 * Returns the array version of the Koded version.
 * Checks the correctness of the provided version array.
 *
 * @param array $version
 * @return array Koded segmented version as array
 */
function get_complete_version(array $version): array
{
    if (false === empty($version)) {
        \assert(3 === \count($version), 'version array should have exactly 3 parts');
        \assert('' !== $version[1], 'pre-release is empty, should be zero or valid identifier');
        \assert('' !== $version[2], 'build-metadata is empty, should be zero or valid identifier');
        return $version;
    }
//    if ('VERSION' !== VERSION && is_array(VERSION)) {
    if (\defined('VERSION') && \is_array(VERSION)) {
        return get_version_array(\join('-', \array_filter(VERSION)));
    }
    if ($version = @\file_get_contents(__DIR__ . '/../../VERSION')) {
        return get_version_array($version);
    }
    return INVALID_VERSION_ARRAY;
}

/**
 * @internal
 *
 * Returns the the major version from VERSION file.
 *
 * @param array $version
 * @return int The major version
 */
function get_major_version(array $version): int
{
    return (int)get_complete_version($version)[0];
}

/**
 * @internal
 *
 * The result is the UTC timestamp of the changeset in "YmDHis" format.
 * This value is not guaranteed to be unique, but it is sufficient
 * for generating the development version numbers.
 *
 * @return string Returns the numeric identifier of the latest GIT changeset,
 * or root directory modification time on failure
 */
function get_git_changeset(): string
{
    $format = 'YmdHis';
    $gitlog = \proc_open('git log --pretty=format:%ct --quiet -l HEAD', [
        ['pipe', 'r'],
        ['pipe', 'w'],
        ['pipe', 'w'],
    ], $pipes, __DIR__);
    if (false === \is_resource($gitlog)) {
        return \date($format, \filemtime(__DIR__ . '/.'));
    }
    \stream_set_blocking($pipes[2], false);
    $timestamp = \stream_get_contents($pipes[1]);
    $timestamp = \explode(PHP_EOL, $timestamp)[0] ?? '';
    // cleanup; avoid a deadlock
    \fclose($pipes[0]);
    \fclose($pipes[1]);
    \fclose($pipes[2]);
    \proc_close($gitlog);
    if (empty($timestamp)) {
        return \date($format, \filemtime(__DIR__ . '/.'));
    }
    // UNIX timestamps are stored in UTC
    return \date_create_immutable('@' . $timestamp)->format($format);
}
