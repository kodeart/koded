<?php

namespace Tests\Koded\Framework;

use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    /**
     * Converts the version array to string.
     *
     * @dataProvider versions
     */
    public function testVersionString($index, $version, $result)
    {
        $this->assertSame($version, get_version($result), '(array -> string) #' . $index);
    }

    /**
     * Get the version array from VERSION file.
     */
    public function testVersionFile()
    {
        $version = get_complete_version([]);
        $this->assertCount(3, $version);
    }

    public function testMajorVerison()
    {
        $major = get_major_version([1, 0, 0]);
        $this->assertSame(1, $major);
    }
    /**
     * Converts the string to array version.
     *
     * @dataProvider versions
     */
    public function testVersionArray($index, $version, $result)
    {
        $this->assertSame($result, get_version_array($version), '(string -> array) #' . $index);
    }
    /**
     * Converts the string to array version.
     *
     * @dataProvider moreVersions
     */
    public function testMoreVersionArray($index, $version, $result)
    {
        $this->assertSame($result, get_version_array($version), '(string -> array) #' . $index);
    }

    /**
     * @dataProvider invalidVersions
     */
    public function testInvalidVersions($index, $version)
    {
        $this->assertSame(INVALID_VERSION_ARRAY, get_version_array($version), 'Data row #' . $index);
    }

    /**
     * Special case: if pre-build is alpha with no meta, git's latest CHANGESET is appended as metadata
     */
    public function testAlphaPreReleaseWithZeroBuild()
    {
        if (!file_exists(__DIR__ . '/../VERSION')) {
            $this->markTestSkipped('The VERSION file is not provided, test is skipped');
        }

        $this->assertMatchesRegularExpression('~(3.0.0-alpha)\+[0-9]{14}~', get_version(['3.0.0', 'alpha', '0']));
        $this->assertMatchesRegularExpression('~(3.0.0-ALPHA)\+[0-9]{14}~', get_version(['3.0.0', 'ALPHA', '0']));
    }

    /*
     *
     * Data providers
     *
     */

    public function versions()
    {
        return [
            // X.Y.Z
            [1, '1.0.0', ['1.0.0', '0', '0']],
            [2, '2.11.3', ['2.11.3', '0', '0']],

            // with pre-release and (optional) number
            [3, '1.10.0-alpha.1', ['1.10.0', 'alpha.1', '0']],
            [4, '1.0.0-beta', ['1.0.0', 'beta', '0']],
            [5, '1.0.0-rc.5', ['1.0.0', 'rc.5', '0']],
            [6, '1.0.0-ALPHA+8', ['1.0.0', 'ALPHA', '8']],

            // undefined pre-release with build-metadata
            [7, '1.7.0-final', ['1.7.0', 'final', '0']],
            [8, '1.0.0+final', ['1.0.0', '0', 'final']],

            // weirdos
            [9, '4.0.0-beta+exp.sha.5114f85', ['4.0.0', 'beta', 'exp.sha.5114f85']],
            [10, '4.0.0-2.3.7.92+7.5.12', ['4.0.0', '2.3.7.92', '7.5.12']],
            [11, '1.0.0-alpha.beta+foo', ['1.0.0', 'alpha.beta', 'foo']],
            [12, '2.9.2+zdfsg1-4ubuntu0.2', ['2.9.2', '0', 'zdfsg1-4ubuntu0.2']],
        ];
    }

    public function moreVersions()
    {
        return [
            [1, '0.0.4', ['0.0.4', '0', '0']],
            [2, '1.2.3', ['1.2.3', '0', '0']],
            [3, '10.20.30', ['10.20.30', '0', '0']],
            [4, '1.1.2-prerelease+meta', ['1.1.2', 'prerelease', 'meta']],
            [5, '1.1.2+meta', ['1.1.2', '0', 'meta']],
            [6, '1.1.2+meta-valid', ['1.1.2', '0', 'meta-valid']],
            [7, '1.0.0-alpha', ['1.0.0', 'alpha', '0']],
            [8, '1.0.0-beta', ['1.0.0', 'beta', '0']],
            [9, '1.0.0-alpha.beta', ['1.0.0', 'alpha.beta', '0']],
            [10, '1.0.0-alpha.beta.1', ['1.0.0', 'alpha.beta.1', '0']],
            [11, '1.0.0-alpha.1', ['1.0.0', 'alpha.1', '0']],
            [12, '1.0.0-alpha0.valid', ['1.0.0', 'alpha0.valid', '0']],
            [13, '1.0.0-alpha.0valid', ['1.0.0', 'alpha.0valid', '0']],
            [14, '1.0.0-alpha-a.b-c-somethinglong+build.1-aef.1-its-okay', ['1.0.0', 'alpha-a.b-c-somethinglong', 'build.1-aef.1-its-okay']],
            [15, '1.0.0-rc.1+build.1', ['1.0.0', 'rc.1', 'build.1']],
            [16, '2.0.0-rc.1+build.123', ['2.0.0', 'rc.1', 'build.123']],
            [17, '1.2.3-beta', ['1.2.3', 'beta', '0']],
            [18, '10.2.3-DEV-SNAPSHOT', ['10.2.3', 'DEV-SNAPSHOT', '0']],
            [19, '1.2.3-SNAPSHOT-123', ['1.2.3', 'SNAPSHOT-123', '0']],
            [20, '2.0.0+build.1848', ['2.0.0', '0', 'build.1848']],
            [21, '2.0.1-alpha.1227', ['2.0.1', 'alpha.1227', '0']],
            [22, '1.2.3----RC-SNAPSHOT.12.9.1--.12+788', ['1.2.3', '---RC-SNAPSHOT.12.9.1--.12', '788']],
            [23, '1.2.3----R-S.12.9.1--.12+meta', ['1.2.3', '---R-S.12.9.1--.12', 'meta']],
            [24, '1.2.3----RC-SNAPSHOT.12.9.1--.12', ['1.2.3', '---RC-SNAPSHOT.12.9.1--.12', '0']],
            [25, '1.0.0+0.build.1-rc.10000aaa-kk-0.1', ['1.0.0', '0', '0.build.1-rc.10000aaa-kk-0.1']],
            [26, '99999999999999999999999.999999999999999999.99999999999999999', ['99999999999999999999999.999999999999999999.99999999999999999', '0', '0']],
            [27, '1.0.0-0A.is.legal', ['1.0.0', '0A.is.legal', '0']],
        ];
    }

    public function invalidVersions()
    {
        return [
            [13, '1.0', INVALID_VERSION_ARRAY],
            [14, '2', INVALID_VERSION_ARRAY],
            [15, '-1', INVALID_VERSION_ARRAY],
            [16, '-2.0.1', INVALID_VERSION_ARRAY],
            [17, '2.0.-1', INVALID_VERSION_ARRAY],
        ];
    }
}
