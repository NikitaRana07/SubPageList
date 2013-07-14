<?php

namespace Tests\Integration\SubPageList\UI;

use SubPageList\Page;
use SubPageList\UI\WikitextSubPageListRenderer;
use Title;

/**
 * @file
 * @ingroup SubPageList
 * @group SubPageList
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikitextSubPageListRendererTest extends \PHPUnit_Framework_TestCase {

	public function testCanRenderMainPage() {
		$page = new Page( Title::newMainPage() );
		$hierarchyRendering = 'foo bar baz';

		$hierarchyRenderer = $this->getMock( 'SubPageList\UI\HierarchyRenderingBehaviour' );

		$hierarchyRenderer->expects( $this->once() )
			->method( 'renderHierarchy' )
			->with( $this->equalTo( $page ) )
			->will( $this->returnValue( $hierarchyRendering ) );

		$renderer = new WikitextSubPageListRenderer( $hierarchyRenderer );

		$text = $renderer->render( $page );

		$this->assertInternalType( 'string', $text );
		$this->assertEquals( $hierarchyRendering, $text );
	}

}
