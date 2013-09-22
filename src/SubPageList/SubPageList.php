<?php 

namespace SubPageList;

use LogicException;
use ParamProcessor\ProcessingResult;
use Parser;
use ParserHooks\HookHandler;
use SubPageList\UI\SubPageListRenderer;
use Title;
use ParamProcessor\ProcessedParam;

/**
 * Handler for the subpagelist parser hook.
 *
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SubPageList implements HookHandler {

	protected $subPageFinder;
	protected $pageHierarchyCreator;
	protected $subPageListRenderer;
	protected $titleFactory;

	public function __construct( SubPageFinder $finder, PageHierarchyCreator $hierarchyCreator,
		SubPageListRenderer $renderer, TitleFactory $titleFactory ) {

		$this->subPageFinder = $finder;
		$this->pageHierarchyCreator = $hierarchyCreator;
		$this->subPageListRenderer = $renderer;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @see HookHandler::handle
	 *
	 * @since 1.0
	 *
	 * @param Parser $parser
	 * @param ProcessingResult $result
	 *
	 * @return string
	 */
	public function handle( Parser $parser, ProcessingResult $result ) {
		if ( $result->hasFatal() ) {
			// This should not occur given the current parameter definitions.
			return 'Error: invalid input into subPageList function';
		}

		$parameters = $this->paramsToOptions( $result->getParameters() );

		$titleText = $parameters['page'];
		$title = $this->titleFactory->newFromText( $titleText );

		if ( $title !== null ) {
			return $this->renderForTitle( $title, $parameters );
		}

		return 'Error: invalid title provided'; // TODO (might want to use a title param...)
	}

	/**
	 * @param Title $title
	 * @param ProcessedParam[] $parameters
	 *
	 * @return string
	 */
	protected function renderForTitle( Title $title, array $parameters ) {
		$topLevelPage = $this->getPageHierarchy( $title );

		if ( $this->shouldUseDefault( $topLevelPage, $parameters['showpage'] ) ) {
			return $this->getDefault( $parameters['page'], $parameters['default'] );
		}
		else {
			return $this->getRenderedList( $topLevelPage, $parameters );
		}
	}

	/**
	 * @param Title $title
	 *
	 * @return Page
	 * @throws LogicException
	 */
	protected function getPageHierarchy( Title $title ) {
		$subPageTitles = $this->subPageFinder->getSubPagesFor( $title );
		$subPageTitles[] = $title;

		$pageHierarchy = $this->pageHierarchyCreator->createHierarchy( $subPageTitles );

		if ( count( $pageHierarchy ) !== 1 ) {
			throw new LogicException( 'Expected exactly one top level page' );
		}

		$topLevelPage = reset( $pageHierarchy );
		return $topLevelPage;
	}

	protected function shouldUseDefault( Page $topLevelPage, $showTopLevelPage ) {
		// Note: this behaviour is not fully correct.
		// Other parameters that omit results need to be held into account as well.
		return !$showTopLevelPage && $topLevelPage->getSubPages() === array();
	}

	protected function getRenderedList( Page $topLevelPage, $parameters ) {
		return $this->subPageListRenderer->render(
			$topLevelPage,
			$parameters
		);
	}

	protected function getDefault( $titleText, $default ) {
		if ( $default === '' ) {
			return "\"$titleText\" has no sub pages."; // TODO
		}

		if ( $default === '-' ) {
			return '';
		}

		return $default;
	}

	/**
	 * @param ProcessedParam[] $parameters
	 *
	 * @return array
	 */
	protected function paramsToOptions( array $parameters ) {
		$options = array();

		foreach ( $parameters as $parameter ) {
			$options[$parameter->getName()] = $parameter->getValue();
		}

		return $options;
	}

}
