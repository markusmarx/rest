<?php
/*
 *  Copyright notice
 *
 *  (c) 2014 Daniel Corn <info@cundd.net>, cundd
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

namespace Cundd\Rest\Tests\Unit\Core;

use Cundd\Rest\Configuration\TypoScriptConfigurationProvider;
use Cundd\Rest\Domain\Model\Format;
use Cundd\Rest\Domain\Model\ResourceType;
use Cundd\Rest\Request;
use Cundd\Rest\RequestFactory;
use Cundd\Rest\RequestFactoryInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;


/**
 * Test case for class new \Cundd\Rest\RequestFactory
 *
 * @version   $Id$
 * @copyright Copyright belongs to the respective authors
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 *
 * @author    Daniel Corn <cod@(c) 2014 Daniel Corn <info@cundd.net>, cundd.li>
 */
class RequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestFactoryInterface
     */
    protected $fixture;

    public function setUp()
    {
        parent::setUp();

        $this->fixture = $this->buildRequestFactory();
    }

    public function tearDown()
    {
        unset($this->fixture);
        unset($_GET['u']);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getUriTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1';
        $request = $this->fixture->getRequest();
        $this->assertEquals('/MyExt-MyModel/1', $request->getPath());
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function getUriWithFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/2.json';
        $request = $this->fixture->getRequest();
        $this->assertEquals('/MyExt-MyModel/2', $request->getPath());
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function getUriWithHtmlFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/2.html';
        $request = $this->fixture->getRequest();
        $this->assertEquals('/MyExt-MyModel/2', $request->getPath());
        $this->assertEquals('html', $request->getFormat());
    }

    /**
     * @test
     */
    public function getAliasUriTest()
    {
        $_GET['u'] = 'myAlias/1';
        $request = $this->fixture->getRequest();
        $this->assertEquals('/MyExt-MyModel/1', $request->getPath());
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function getAliasUriWithFormatTest()
    {
        $_GET['u'] = 'myAlias/2.json';
        $request = $this->fixture->getRequest();
        $this->assertEquals('/MyExt-MyModel/2', $request->getPath());
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function getAliasUriWithHtmlFormatTest()
    {
        $_GET['u'] = 'myAlias/2.html';
        $request = $this->fixture->getRequest();
        $this->assertEquals('/MyExt-MyModel/2', $request->getPath());
        $this->assertEquals('html', $request->getFormat());
    }

    /**
     * @test
     */
    public function getOriginalResourceTypeTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1';
        /** @var Request $request */
        $request = $this->fixture->getRequest();
        $this->assertEquals('MyExt-MyModel', $request->getOriginalResourceType());
    }

    /**
     * @test
     */
    public function getOriginalResourceTypeWithFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/2.json';
        /** @var Request $request */
        $request = $this->fixture->getRequest();
        $this->assertEquals('MyExt-MyModel', $request->getOriginalResourceType());
    }

    /**
     * @test
     */
    public function getRootObjectKeyTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1';
        $request = $this->fixture->getRequest();
        $this->assertEquals('MyExt-MyModel', $request->getRootObjectKey());
    }

    /**
     * @test
     */
    public function getRootObjectKeyWithFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/2.json';
        $request = $this->fixture->getRequest();
        $this->assertEquals('MyExt-MyModel', $request->getRootObjectKey());
    }

    /**
     * @test
     */
    public function getPathTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1';
        $path = $this->fixture->getRequest()->getResourceType();
        $this->assertEquals('MyExt-MyModel', $path);
    }

    /**
     * @test
     */
    public function getPathWithFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1.json';
        $path = $this->fixture->getRequest()->getResourceType();
        $this->assertEquals('MyExt-MyModel', $path);
    }

    /**
     * @test
     */
    public function getUnderscoredPathWithFormatAndIdTest()
    {
        $_GET['u'] = 'my_ext-my_model/1.json';
        $path = $this->fixture->getRequest()->getResourceType();
        $this->assertEquals('my_ext-my_model', $path);
    }

    /**
     * @test
     */
    public function getUnderscoredPathWithFormatTest2()
    {
        $_GET['u'] = 'my_ext-my_model.json';
        $path = $this->fixture->getRequest()->getResourceType();
        $this->assertEquals('my_ext-my_model', $path);
    }

    /**
     * @test
     */
    public function getFormatWithoutFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1';
        $request = $this->fixture->getRequest();
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function getFormatWithFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1.json';
        $request = $this->fixture->getRequest();
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function getFormatWithoutPathTest()
    {
        $_GET['u'] = '.json';
        $request = $this->fixture->getRequest();
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function getFormatWithHtmlFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1.html';
        $request = $this->fixture->getRequest();
        $this->assertEquals('html', $request->getFormat());
    }

    /**
     * @test
     */
    public function getFormatWithDecimalSegmentJsonFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1.0.json';
        $request = $this->fixture->getRequest();
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function getFormatWithDecimalSegmentHtmlFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1.0.html';
        $request = $this->fixture->getRequest();
        $this->assertEquals('html', $request->getFormat());
    }

    /**
     * @test
     */
    public function getFormatWithDecimalSegmentTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1.0';
        $request = $this->fixture->getRequest();
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function getFormatWithNotExistingFormatTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1.blur';
        $request = $this->fixture->getRequest();
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function getUriWithAbsRefPrefixInSubDirectoryTest()
    {
        $_SERVER['REQUEST_URI'] = '/subDirectory/rest/MyExt-MyModel/1';
        $request = $this->buildRequestFactory(array('absRefPrefix' => '/subDirectory/'))->getRequest();
        $this->assertEquals('/MyExt-MyModel/1', $request->getPath());
    }

    /**
     * @test
     */
    public function getUriWithAbsRefPrefixInSubDirectoryWithoutTrailingSlashTest()
    {
        $_SERVER['REQUEST_URI'] = '/subDirectory/rest/MyExt-MyModel/1';
        $request = $this->buildRequestFactory(array('absRefPrefix' => '/subDirectory'))->getRequest();
        $this->assertEquals('/MyExt-MyModel/1', $request->getPath());
    }

    /**
     * @test
     */
    public function getUriWithAbsRefPrefixSlashTest()
    {
        $_SERVER['REQUEST_URI'] = '/rest/MyExt-MyModel/1';
        $request = $this->buildRequestFactory(array('absRefPrefix' => '/'))->getRequest();
        $this->assertEquals('/MyExt-MyModel/1', $request->getPath());
    }

    /**
     * @test
     */
    public function getUriWithAbsRefPrefixDomainTest()
    {
        $_SERVER['REQUEST_URI'] = '/rest/MyExt-MyModel/1';
        $request = $this->buildRequestFactory(array('absRefPrefix' => 'http://example.com/'))->getRequest();
        $this->assertEquals('/MyExt-MyModel/1', $request->getPath());
    }

    /**
     * @test
     */
    public function getUriWithAbsRefPrefixAutoTest()
    {
        $_SERVER['REQUEST_URI'] = '/rest/MyExt-MyModel/1';
        $request = $this->buildRequestFactory(array('absRefPrefix' => 'auto'))->getRequest();
        $this->assertEquals('/MyExt-MyModel/1', $request->getPath());
    }

    /**
     * @test
     */
    public function pathShouldNotIncludeQueryDataTest()
    {
        $_GET['u'] = 'MyExt-MyModel/1?query=string';
        $request = $this->buildRequestFactory()->getRequest();
        $this->assertEquals('MyExt-MyModel', $request->getResourceType());
        $this->assertEquals('json', $request->getFormat());

        $_GET['u'] = 'MyExt-MyModel/?query=string';
        $request = $this->buildRequestFactory()->getRequest();
        $this->assertEquals('MyExt-MyModel', $request->getResourceType());
        $this->assertEquals('json', $request->getFormat());

        $_GET['u'] = 'MyExt-MyModel?query=string';
        $request = $this->buildRequestFactory()->getRequest();
        $this->assertEquals('MyExt-MyModel', $request->getResourceType());
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function urlAndPathShouldNotIncludeQueryDataFromRequestUriTest()
    {
        $_SERVER['REQUEST_URI'] = '/rest/MyExt-MyModel/1?query=string';
        $request = $this->buildRequestFactory()->getRequest();
        $this->assertEquals('MyExt-MyModel', $request->getResourceType());
        $this->assertEquals('/MyExt-MyModel/1', $request->getPath());
        $this->assertEquals('json', $request->getFormat());

        $_SERVER['REQUEST_URI'] = '/rest/MyExt-MyModel/?query=string';
        $request = $this->buildRequestFactory()->getRequest();
        $this->assertEquals('MyExt-MyModel', $request->getResourceType());
        $this->assertEquals('/MyExt-MyModel/', $request->getPath());
        $this->assertEquals('json', $request->getFormat());

        $_SERVER['REQUEST_URI'] = '/rest/MyExt-MyModel?query=string';
        $request = $this->buildRequestFactory()->getRequest();
        $this->assertEquals('MyExt-MyModel', $request->getResourceType());
        $this->assertEquals('/MyExt-MyModel', $request->getPath());
        $this->assertEquals('json', $request->getFormat());
    }

    /**
     * @test
     * @dataProvider createRequestTestDataProvider
     */
    public function createRequestTest($input, $resourceType, $path, $format)
    {
        $_SERVER['REQUEST_URI'] = $input;
        $request = $this->buildRequestFactory()->getRequest();
        $this->assertInstanceOf(ResourceType::class, $request->getResourceType());
        $this->assertSame($resourceType, (string)$request->getResourceType());
        $this->assertSame($path, $request->getPath());
        $this->assertInstanceOf(Format::class, $request->getFormat());
        $this->assertSame($format, (string)$request->getFormat());
    }

    public function createRequestTestDataProvider()
    {
        return [
            ['/rest/MyExt-MyModel', 'MyExt-MyModel', '/MyExt-MyModel', 'json'],
            ['/rest/MyExt-MyModel/', 'MyExt-MyModel', '/MyExt-MyModel/', 'json'],
            ['/rest/MyExt-MyModel/1.0', 'MyExt-MyModel', '/MyExt-MyModel/1.0', 'json'],
            ['/rest/MyExt-MyModel/1.0.json', 'MyExt-MyModel', '/MyExt-MyModel/1.0', 'json'],
            ['/rest/MyExt-MyModel/1.0.html', 'MyExt-MyModel', '/MyExt-MyModel/1.0', 'html'],
            ['/rest/MyExt-MyModel/198.0.html', 'MyExt-MyModel', '/MyExt-MyModel/198.0', 'html'],
            ['/rest/MyExt-MyModel/19.80', 'MyExt-MyModel', '/MyExt-MyModel/19.80', 'json'],
            ['/rest/MyExt-MyModel/19.80.html', 'MyExt-MyModel', '/MyExt-MyModel/19.80', 'html'],
            ['/rest/MyExt-MyModel/19.8.html', 'MyExt-MyModel', '/MyExt-MyModel/19.8', 'html'],
        ];
    }

    /**
     * @param array $configurationProviderSetting
     * @return RequestFactory
     */
    private function buildRequestFactory($configurationProviderSetting = [])
    {
        /** @var TypoScriptConfigurationProvider|ObjectProphecy $configurationProviderMock */
        $configurationProviderMock = $this->prophesize(TypoScriptConfigurationProvider::class);

        if (empty($configurationProviderSetting)) {
            $configurationProviderSetting = array(
                'aliases.myAlias' => 'MyExt-MyModel',
            );
        }
        $configurationProviderMock->getSetting(Argument::type('string'))->will(
            function ($args) use ($configurationProviderSetting) {
                if (isset($args[0])) {
                    $key = $args[0];

//                    echo __LINE__.' ';var_dump($key);
//                    echo __LINE__.' ';var_dump($configurationProviderSetting);
//                    echo __LINE__.' ';var_dump(isset($configurationProviderSetting[$key]));

                    return isset($configurationProviderSetting[$key]) ? $configurationProviderSetting[$key] : null;
                }

                return null;
            }
        );

        $_SERVER['SERVER_NAME'] = 'rest.cundd.net';

        return new RequestFactory($configurationProviderMock->reveal(), \Zend\Diactoros\ServerRequestFactory::class);
    }
}
