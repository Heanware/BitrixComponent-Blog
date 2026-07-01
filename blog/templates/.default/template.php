<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);

addScssFile(SITE_TEMPLATE_PATH . '/assets/styles/blog.scss');
?>
<div class="container">
    <div class="blog">
        <div class="breadcrumbs"><?php echo $arResult['BREADCRUMBS'] ?></div>
        <?php if(!$arResult['IS_DETAIL']): ?>
            <h1><?=$arResult['META_VARIABLES']['HEADER'] ?? $arResult['NAME']?></h1>
        <?php endif; ?>
        <div class="main">
            <aside <?=$arResult['IS_DETAIL'] ? 'class="nosearch"' : ''?>>
                <nav aria-label="Разделы блога">
                    <ul class="blog__sort" role="list">
                        <?php foreach($arResult['NAV'] as $code => $sort): ?>
                            <?php if($sort === 'SEPARATOR'): ?>
                                <li class="blog__sort-separator">
                                    <span>Темы</span>
                                </li>
                                <?php continue; ?>
                            <?php endif; ?>
                            <li data-code="<?=$code?>" <?=$sort['ACTIVE'] ? 'class="active"' : ''?>>
                                <a href="/blog/<?=$code?>/">
                                    <?php if(!empty($sort['ICON'])): ?>
                                        <svg aria-hidden="true" width="24" height="24">
                                            <use href="<?=$sort['ICON']?>"></use>
                                        </svg>
                                    <?php endif; ?>
                                    <?=$sort['NAME']?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                <svg aria-hidden="true" width="24" height="24" class="blog-search-caret">
                    <use href="#caret"></use>
                </svg>
            </aside>
            <section class="main__articles-wrapper">
                <?php if(!$arResult['IS_DETAIL']): ?>
                    <form action="#" class="main__articles--search js-articles-search">
                        <input type="text" placeholder="Поиск по статьям" id="blog-search">
                        <label for="blog-search" class="visibility-hidden">Поиск статей в блоге</label>
                        <button class="visibility-hidden" type="submit">Искать</button>
                        <svg aria-hidden="true" width="24" height="24" class="articles-search">
                            <use href="#search"></use>
                        </svg>
                        <svg aria-hidden="true" width="24" height="24" class="articles-close-search">
                            <use href="#close"></use>
                        </svg>
                    </form>
                    <span class="search-message js-message" aria-live="polite" aria-atomic="true"></span>
                    <div class="main__articles js-main-articles" data-code="<?=$arResult['PAGE_VARIABLES']['CODE']?>">
                        <?php foreach($arResult['ITEMS'] as $key => $article): ?>
                            <?php require(__DIR__ . '/article.php'); ?>
                        <?php endforeach; ?>
                    </div>
                    <div id="main__articles--trigger"></div>
                <?php elseif(!empty($article = $arResult['ITEM'])): ?>
                    <article class="main__article">
                        <header class="main__article--header">
                            <ul class="main__article--header-labels blog-article-labels" role="list">
                                <li class="main__article--header-labels-theme blog-article-labels-theme">
                                    <a href="/blog/<?=$article['SECTION_CODE']?>/">
                                        <?=$article['SECTION_NAME']?>
                                    </a>
                                </li>
                                <?php if($article['IS_NEW']): ?>
                                    <li class="article-new">
                                        <a href="/blog/novoe/">
                                            <svg aria-hidden="true" width="14" height="14">
                                                <use href="#clock"></use>
                                            </svg>
                                            Новое
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php if($article['IS_POPULAR']): ?>
                                    <li class="article-popular">
                                        <a href="/blog/populyarnoe/">
                                            <svg aria-hidden="true" width="14" height="14">
                                                <use href="#fire"></use>
                                            </svg>
                                            Популярное
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            <h1><?=$article['NAME']?></h1>
                            <ul class="main__article--header-creation" aria-label="Информация о статье" role="list">
                                <li>
                                    Опубликовано:
                                    <time datetime="<?=date('Y-m-d', strtotime($article['DATE_CREATE']))?>"><?=$article['DATE_CREATE']->format('d.m.Y')?></time>
                                </li>
                                <?php if(!empty($article['TIMESTAMP_X']) && $article['DATE_CREATE'] !== $article['TIMESTAMP_X']): ?>
                                    <li aria-hidden="true">
                                        <span>|</span>
                                    </li>
                                    <li>
                                        Изменено:
                                        <time datetime="<?=$article['TIMESTAMP_X']->format('Y-m-d')?>"><?=$article['TIMESTAMP_X']->format('d.m.Y')?></time>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </header>
                        <section class="main__article--content">
                            <?php if(!empty($detailPicture = $article['DETAIL_PICTURE'])): ?>
                                <img class="main__article--content-img" src="<?=$detailPicture['SRC']?>" alt="<?=!empty($detailPicture['DESCRIPTION']) ? $detailPicture['DESCRIPTION'] : $article['NAME']?>" title="<?=$article['NAME']?>" loading="lazy">
                            <?php endif; ?>
                            <?=$article['DETAIL_TEXT']?>
                        </section>
                        <footer class="main__article--footer">
                            <ul class="main__article--footer-meta" role="list" aria-label="Автор статьи и количество просмотров">
                                <li>
                                    <span><?=$article['AUTHOR']?></span>
                                </li>
                                <li>
                                    <span class="visibility-hidden">Количество просмотров:</span>
                                    <span>
                                        <svg aria-hidden="true" width="18" height="18">
                                            <use href="#eye"></use>
                                        </svg>
                                        <?=$article['SHOW_COUNTER']?>
                                    </span>
                                </li>
                            </ul>
                        </footer>
                    </article>
                    <?php if(!empty($readAlso = $article['READ_ALSO'])): ?>
                        <section class="main__articles--also">
                            <header class="main__articles--also-header">
                                <h2>Читайте также</h2>
                            </header>
                            <div class="main__articles--also-swiper-wrapper js-blog-swiper swiper">
                                <div class="main__articles--also-swiper swiper-wrapper" role="list">
                                    <?php foreach($readAlso as $item): ?>
                                        <article class="swiper-slide main__articles--also-swiper-item" role="listitem">
                                            <a href="/blog/<?=$item['CODE']?>/">
                                                <?php if(!empty($previewPicture = $item['PREVIEW_PICTURE'])): ?>
                                                    <img src="<?=$previewPicture['SRC']?>" alt="<?=!empty($previewPicture['DESCRIPTION']) ? $previewPicture['DESCRIPTION'] : $item['NAME']?>" title="<?=$item['NAME']?>" loading="lazy">
                                                <?php endif; ?>
                                                <h3><?=$item['NAME']?></h3>
                                                <time datetime="<?=date('Y-m-d', strtotime($item['DATE_CREATE']))?>"><?=$item['DATE_CREATE']->format('d.m.Y')?></time>
                                            </a>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>