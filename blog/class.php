<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Web\Cookie;
use Util\Searcher;
use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Grid\Declension;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\Elements\ElementBlogTable;
use Bitrix\Iblock\InheritedProperty\ElementValues;
use Bitrix\Iblock\InheritedProperty\SectionValues;

class Blog extends CBitrixComponent implements Controllerable
{
    private $defaultSelectFields = [
        'ID',
        'IBLOCK_SECTION.NAME',
        'IBLOCK_SECTION.CODE',
        'NAME',
        'CODE',
        'DATE_CREATE',
        'TIMESTAMP_X',
        'PREVIEW_PICTURE',
        'PREVIEW_TEXT',
        'AUTHOR',
        'RATING',
        'LISTING_TITLE',
        'SHOW_COUNTER',
    ];

    private float $popularityIndex = 7372; // might change

    private float $timeCost = 240000; // might change

    private bool $noSectionSelected = true;

    public function onPrepareComponentParams($arParams): array
    {
        return $arParams;
    }

    public function configureActions(): array
    {
        return [
            'search' => [
                'prefilters' => [
                    new  ActionFilter\Csrf(),
                ],
            ],
            'loadMore' => [
                'prefilters' => [
                    new  ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    private function getQueryParams(?string $code): array
    {
        if($code === 'novoe'){
            $params = [
                'filter' => ['>DATE_CREATE' => (new DateTime())->add('-7D')],
                'order' => ['TIMESTAMP_X' => 'DESC', 'DATE_CREATE' => 'DESC']
            ];
        }else if($code === 'populyarnoe'){
            $params = [
                'order' => ['RATING' => 'DESC']
            ];
        }else if(array_key_exists($code, $this->getActiveSections($code) ?? [])){
            $params = [
                'filter' => ['IBLOCK_SECTION.CODE' => $code],
                'order' => ['NAME' => 'DESC']
            ];
        }else{
            $params = [
                'order' => ['DATE_CREATE' => 'DESC']
            ];
        }

        return $params;
    }

    private function getPageMeta(string $code = null): array
    {
        if($code === 'novoe'){
            return [
                'HEADER' => 'Новое',
                'TITLE' => 'Новые статьи',
                'DESCRIPTION' => 'Новые статьи',
            ];
        }else if($code === 'populyarnoe'){
            return [
                'HEADER' => 'Популярное',
                'TITLE' => 'Популярные статьи',
                'DESCRIPTION' => 'Популярные статьи',
            ];
        }else if(array_key_exists($code, $this->arResult['NAV'] ?? []) && $sectionMeta = new SectionValues(BLOG_IBLOCK, $this->arResult['NAV'][$code]['ID'])){
            return [
                'HEADER' => $sectionMeta->getValue('SECTION_PAGE_TITLE'),
                'TITLE' => $sectionMeta->getValue('SECTION_META_TITLE'),
                'DESCRIPTION' => $sectionMeta->getValue('SECTION_META_DESCRIPTION'),
            ];
        }else if(!empty($this->arResult['ITEM']) && $elementMeta = new ElementValues(BLOG_IBLOCK, $this->arResult['ITEM']['ID'])){
            return [
                'HEADER' => $elementMeta->getValue('ELEMENT_PAGE_TITLE'),
                'TITLE' => $elementMeta->getValue('ELEMENT_META_TITLE'),
                'DESCRIPTION' => $elementMeta->getValue('ELEMENT_META_DESCRIPTION'),
            ];
        }else{
            return [
                'HEADER' => 'Блог',
                'TITLE' => 'Блог',
                'DESCRIPTION' => 'Статьи',
            ];
        }
    }

    private function getActiveSections(?string $code): array
    {
        $sections = SectionTable::getList([
            'order' => ['NAME' => 'ASC'],
            'filter' => ['IBLOCK_ID' => BLOG_IBLOCK, 'ACTIVE' => 'Y'],
            'select' => ['ID', 'NAME', 'CODE'],
        ])->fetchCollection();

        $result = [];

        foreach($sections as $section){
            $sectionId = $section->getId();
            $sectionCode = $section->getCode();

            if($sectionCode === $code) $this->noSectionSelected = false;

            $result[$sectionCode] = [
                'ID' => $sectionId,
                'NAME' => $section->getName(),
                'ACTIVE' => $sectionCode === $code,
            ];
        }
        return $result;
    }

    private function getNav(?string $code): void
    {
        $sections = $this->getActiveSections($code);

        $isPopular = ($code === 'populyarnoe');
        $isDefault = ($code === 'novoe' || $code === null || ($this->noSectionSelected && !$isPopular));

        $this->arResult['NAV'] = [
            'novoe' => [
                'NAME' => 'Новое',
                'ACTIVE' => $isDefault,
                'ICON' => '#clock',
            ],
            'populyarnoe' => [
                'NAME' => 'Популярное',
                'ACTIVE' => $isPopular,
                'ICON' => '#fire'
            ],
            'SEPARATOR',
            ...$sections,
        ];
    }

    private function getBreadcrumbs(?string $code = null): string
    {
        $breadcrumbs = [
            [
                'TITLE' => 'Главная',
                'LINK' => '/blog/',
            ],
        ];

        if($code === 'novoe'){
            $breadcrumbs[] = [
                'TITLE' => 'Новое',
                'LINK' => '/blog/novoe/',
            ];
        }else if($code === 'populyarnoe'){
            $breadcrumbs[] = [
                'TITLE' => 'Популярное',
                'LINK' => '/blog/populyarnoe/',
            ];
        }else if(array_key_exists($code, $this->arResult['NAV'] ?? [])){
            $breadcrumbs[] = [
                'TITLE' => $this->arResult['NAV'][$code]['NAME'],
                'LINK' => '/blog/' . $code . '/',
            ];
        }else if(!empty($this->arResult['ITEM'])){
            $sectionCode = $this->arResult['ITEM']['SECTION_CODE'];
            $breadcrumbs[] = [
                'TITLE' => $this->arResult['NAV'][$sectionCode]['NAME'],
                'LINK' => '/blog/' . $sectionCode . '/',
            ];
            $breadcrumbs[] = [
                'TITLE' => $this->arResult['ITEM']['NAME'],
                'LINK' => '/blog/' . $this->arResult['ITEM']['CODE'] . '/',
            ];
        }else{
            return '';
        }

        $strReturn = '<div class="blog-breadcrumbs bx-breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">';
        $itemSize = count($breadcrumbs);

        for($index = 0; $index < $itemSize; $index++){
            $title = htmlspecialcharsex($breadcrumbs[$index]["TITLE"]);
            $arrow = ($index > 0 ? '/' : '');

            if($breadcrumbs[$index]["LINK"] <> ""){
                $strReturn .= '
            <div class="bx-breadcrumb-item" id="bx_breadcrumb_' . $index . '" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a href="' . $breadcrumbs[$index]["LINK"] . '" title="' . $title . '" itemprop="item">
                    <span itemprop="name">' . $title . '</span>
                </a>
                <meta itemprop="position" content="' . ($index + 1) . '">
            </div>';
            }else{
                $strReturn .= '
            <div class="bx-breadcrumb-item">
                <span>' . $title . '</span>
            </div>';
            }
        }

        $strReturn .= '</div>';
        return $strReturn;
    }

    private function getItems(array $select = [], array $filter = [], array $order = [], int $limit = 7, int $offset = 0, array $runtime = []): array
    {
        $baseFilter = [
            'ACTIVE' => 'Y',
            'IBLOCK_SECTION.ACTIVE' => 'Y',
        ];

        if(!empty($filter)){
            $baseFilter[] = $filter;
        }

        //reddit formula
        $runtime[] = new ExpressionField('RATING', 'LOG10(GREATEST(%s, 1)) + (UNIX_TIMESTAMP(%s) / ' . $this->timeCost . ')', ['SHOW_COUNTER', 'DATE_CREATE']);

        $articles = ElementBlogTable::getList([
            'filter' => $baseFilter,
            'select' => array_merge($this->defaultSelectFields, $select),
            'order' => $order,
            'limit' => $limit ?? null,
            'offset' => $limit * $offset,
            'runtime' => $runtime,
        ])->fetchCollection();

        $result = [];

        foreach($articles as $article){
            $result[] = $this->assignFields($article);
        }

        return $result;
    }

    private function tryGetDetail(?string $code): array
    {
        if(!$code) return [];

        $result = $this->getItems(select: ['DETAIL_PICTURE', 'DETAIL_TEXT', 'READ_ALSO.ELEMENT'], filter: ['CODE' => $code]);

        if(!empty($item = $result[0])){
            $context = Context::getCurrent();
            $request = $context->getRequest();
            $response = $context->getResponse();

            if(!$request->getCookie($item['ID'] . '_seen')){
                CIBlockElement::CounterInc($item['ID']);

                $cookie = new Cookie($item['ID'] . '_seen', true, time() + 7 * 24 * 60 * 60);

                $response->addCookie($cookie);
            }

            $response->setLastModified(DateTime::createFromUserTime($item['TIMESTAMP_X']));

            return $item;
        }

        return [];
    }

    private function assignFields(object $item): array
    {
        $section = $item->getIblockSection();
        $result = [
            'ID' => (int)$item->getId(),
            'SECTION_NAME' => $section ? $section->getName() : '',
            'SECTION_CODE' => $section ? $section->getCode() : '',
            'NAME' => $item->getName(),
            'CODE' => $item->getCode() ? $item->getCode() : '',
            'DATE_CREATE' => $item->getDateCreate(),
            'TIMESTAMP_X' => $item->getTimestampX(),
            'PREVIEW_PICTURE' => $item->getPreviewPicture() ? CFile::GetFileArray($item->getPreviewPicture()) : '',
            'DETAIL_PICTURE' => $item->getDetailPicture() ? CFile::GetFileArray($item->getDetailPicture()) : '',
            'PREVIEW_TEXT' => $item->getPreviewText(),
            'DETAIL_TEXT' => $item->getDetailText(),
            'AUTHOR' => $item->getAuthor() ? $item->getAuthor()->getValue() : '',
            'LISTING_TITLE' => $item->getListingTitle() ? $item->getListingTitle()->getValue() : '',
            'SHOW_COUNTER' => $item->get('SHOW_COUNTER'),
        ];

        if($readAlso = $item->getReadAlso()){
            foreach($readAlso->getAll() as $obArticle){
                $addArticle = $obArticle->getElement();
                $result['READ_ALSO'][$addArticle->getId()] = [
                    'NAME' => $addArticle->getName(),
                    'CODE' => $addArticle->getCode(),
                    'DATE_CREATE' => $addArticle->getDateCreate(),
                    'PREVIEW_PICTURE' => $addArticle->getPreviewPicture() ? CFile::GetFileArray($addArticle->getPreviewPicture()) : '',
                ];
            }
        }

        if($rating = $item->get('RATING')){
            $result['IS_POPULAR'] = $rating > $this->popularityIndex;
        }

        if(!empty($result['DATE_CREATE'])){
            $result['IS_NEW'] = new DateTime($result['DATE_CREATE']) >= (new DateTime())->add('-7D');
        }

        return $result;
    }

    private function getPage(string|null $code): array
    {
        if($item = $this->tryGetDetail($code)){
            $this->arResult['ITEM'] = $item;
            $this->arResult['IS_DETAIL'] = true;
            $this->getNav($item['SECTION_CODE']);
        }else{
            $this->arResult['PAGE_VARIABLES']['CODE'] = $code;
            $this->arResult['ITEMS'] = $this->getItems(...$this->getQueryParams($code));
            $this->getNav($code);
        }

        $this->arResult['META_VARIABLES'] = $this->getPageMeta($code);
        $this->arResult['BREADCRUMBS'] = $this->getBreadcrumbs($code);

        if(empty($this->arResult['ITEMS']) && empty($this->arResult['ITEM'])){
            \Bitrix\Main\Context::getCurrent()->getResponse()->setStatus(404);
        }

        return $this->arResult;
    }

    public function searchAction(string $query, string $code)
    {
        if($query !== ""){
            $searchFilter = Searcher::search($query, \Namespace\Constants\IblockIds::Blog);
            $items = $this->getItems(filter: $searchFilter, limit: 0);
            $count = count($items);
            $itemDeclension = new Declension(' элемент', ' элемента', ' элементов');
            $resDeclension = new Declension('Найден ', 'Найдено ', 'Найдено ');
            if($count > 0){
                return [
                    'items' => $this->parseItemsHtml($items),
                    'count' => $count,
                    'message' => $resDeclension->get($count) . $count . $itemDeclension->get($count),
                ];
            }
        }

        return [
            'items' => $this->parseItemsHtml($this->getItems(...$this->getQueryParams($code))),
            'count' => 0,
            'message' => ($query !== "") ? 'Ничего не найдено. <br> Возможно, вас заинтересуют следующие темы' : '',
        ];
    }

    public function loadMoreAction(string $code, int $pageNum)
    {
        $params = $this->getQueryParams($code);
        $params['offset'] = $pageNum;

        return $this->parseItemsHtml($this->getItems(...$params));
    }

    private function parseItemsHtml(array $items)
    {
        if(empty($items)) return [];
        ob_start();
        foreach($items as $article)
            require(__DIR__ . '/templates/.default/article.php');
        return ob_get_clean();
    }

    public function executeComponent(): void
    {
        $request = Context::getCurrent()->getRequest();
        $this->getPage($request->get('CODE'));
        $this->includeComponentTemplate();
    }
}