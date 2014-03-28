<?php
require_once( __DIR__ . '/boot_sample.php' );

try {
?><!DOCTYPE html>
<html class="no-js" lang="ru">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width" />
        <link rel="shortcut icon" href="/images/APM_logo-16x16.png" />
        <link rel="stylesheet" href="css/style.css?ver=<?php attr(ApmCom::Version) ?>" />
        <script src="js/vendor/modernizr-2.7.1.min.js"></script>
        <script type='text/javascript' src='/wp-content/plugins/apmcom/client/ga.js?ver=<?php attr(ApmCom::Version) ?>'></script>
        <title>APM</title>
        <style>
            html.waiting, html.waiting * { cursor: wait !important; }
            .gallery__list__item_current > .gallery__list__item__image { cursor: pointer; }
        </style>
        <!-- Scripts -->
        <script src="js/vendor/jquery-1.10.2.min.js"></script>
        <script src="js/plugins.js?ver=<?php attr(ApmCom::Version) ?>"></script>
        <script src="js/main.js?ver=<?php attr(ApmCom::Version) ?>"></script>

        <!--/Scripts -->
        <!--[if lt IE 9]>
        <script src="js/vendor/respond.js"></script>
        <script src="js/vendor/excanvas.compiled.js"></script>
        <![endif]-->

    </head>
    <body>

        <div class="jazz">
            <ul class="jazz__tower live_events">
                <li class="jazz__tower__floor jazz__tower__floor_about" id="about">
                    <div class="jazz__tower__floor__content">
<?php
$aboutUs = $aca->exec( array( 'opcode' => 'taxonomy_post_terms', 'post_type' => 'page', 'name' => 'about-us' ) );
if ( count( $aboutUs ) === 0 ) {
	throw new Exception( 'Missing required page with slug "about-us"' );
}
// About Us Page.
foreach ( $aboutUs as $AUP ) {
	break;
}
# sdv_debug('AUP before',$AUP);
$AUP->post_content = $aca->multiSpaceToNBSP( $AUP->post_content );
foreach ( $AUP->_api_meta as &$field ) {
	$field = $aca->multiSpaceToNBSP( $field );
}
# sdv_debug('AUP after',$AUP);
# a2o() is useless because custom field names with '-' in their names are used. Use '_' instead ('about_subtitle' etc).
# $AUP = a2o( $AUP );
?>
                        <div class="jazz-about">
                            <div class="jazz-about__head">
                                <div class="jazz-about__head__title">
                                    <div class="jazz-title">О нас</div>
                                </div>
                                <div class="jazz-about__head__bubble">
                                    <i class="jazz-bubble jazz-bubble_about"></i>
                                </div>
                            </div>
                            <div class="jazz-about__primary-text">
                                <div class="jazz-about__primary-text__title"><?php ____($AUP->_api_meta['about-subtitle']) ?></div>
                                <div class="jazz-about__primary-text__prefix"><?php attr($AUP->_api_meta['about-prefix']) ?></div>
                                <div class="jazz-about__primary-text__text"><?php ____($AUP->post_content) ?></div>
                                <div class="jazz-about__primary-text__postfix"><?php attr($AUP->_api_meta['about-postfix']) ?></div>
                            </div>
                        </div>

                    </div>
                </li>
                <li class="jazz__tower__floor jazz__tower__floor_service" id="service">
                    <div class="jazz__tower__floor__content">
<?php
// require_once( ABSPATH . '/templates/mockups/jazz_services_list_mockup.php' );
$servicesResult = $aca->exec( array( 'opcode' => 'jazz_services_list_view' ) );
# sdv_debug('portfolio by services',$servicesResult);
echo $servicesResult['html'];
?>

                                        <div class="popup" id="service-portfolio">
<?php
popup__bar();
?>
                                            <div class="popup__body" id="jazz-portfolio__services__case_placeholder">
<?php
// // require_once( ABSPATH . '/templates/mockups/jazz_services_cases_mockup.php' );
// $casesResult = $aca->exec( array( 'opcode' => 'generic_cases_view', 'case_ids' => $servicesResult['case_ids'] ) );
# sdv_debug('portfolio by services full cases',$casesResult);
// echo $casesResult['html'];
?>
                                            </div>
                                            <div class="popup__footer">

                                            </div>

                                        </div><!-- /popup -->
                    </div>
                </li>
                <li class="jazz__tower__floor jazz__tower__floor_portfolio" id="portfolio">
                    <div class="jazz__tower__floor__content">


                        <div class="jazz-portfolio">
                            <div class="jazz-portfolio__head">
                                <div class="jazz-portfolio__head__bubble">
                                    <i class="jazz-bubble jazz-bubble_portfolio"></i>
                                </div>
                                <div class="jazz-portfolio__head__title">
                                    <div class="jazz-title">Портфолио</div>
                                </div>
                                <ul class="jazz-portfolio__head__nav">
                                    <li class="jazz-portfolio__head__nav__item">
                                        <a class="jazz-portfolio__head__nav__item__link" data-slide=".jazz-portfolio__content__item_clients" href="#">По клиентам</a>
                                    </li>
                                    <li class="jazz-portfolio__head__nav__item">
                                        <a class="jazz-portfolio__head__nav__item__link jazz-portfolio__head__nav__item__link_current" data-slide=".jazz-portfolio__content__item_chaos" href="#">Хаотично</a>
                                    </li>
                                </ul>
                            </div>
                            <ul class="jazz-portfolio__content">
                                <li class="jazz-portfolio__content__item jazz-portfolio__content__item_clients" style="display: none;">
                                    <div data-current-page="1" class="pagination_placeholder" id="jazz-portfolio__clients__list_placeholder">
<?php
// require_once( ABSPATH . '/templates/mockups/jazz_clients_list_mockup.php' );
$clientsResult = $aca->exec( array( 'opcode' => 'jazz_clients_list_view' ) );
# sdv_debug('portfolio by clients',$clientsResult);
echo $clientsResult['html'];
?>
                                    </div>
                                        <div class="popup" id="clients-portfolio">
<?php
popup__bar();
?>
                                            <div class="popup__body" id="jazz-portfolio__clients__case_placeholder">
<?php
// // require_once( ABSPATH . '/templates/mockups/jazz_clients_cases_mockup.php' );
// $casesResult = $aca->exec( array( 'opcode' => 'generic_cases_view', 'case_ids' => $clientsResult['case_ids'] ) );
# sdv_debug('portfolio by clients full cases',$casesResult);
// echo $casesResult['html'];
?>
                                            </div>
                                            <div class="popup__footer">

                                            </div>

                                        </div><!-- /popup -->

                                </li>
                                <li class="jazz-portfolio__content__item jazz-portfolio__content__item_chaos" style="display: block;">
                                    <div class="jazz-chaos">
<?php
// require_once( ABSPATH . '/templates/mockups/jazz_chaos_slots_mockup.php' );
$randomCasesResult = $aca->exec( array( 'opcode' => 'jazz_random_cases_view' ) );
# sdv_debug('random portfolio',$randomCasesResult);
echo $randomCasesResult['html'];
?>
                                        <div class="popup" id="portfolio-chaos">
<?php
popup__bar();
?>
                                        <div class="popup__body" id="jazz-portfolio__random__case_placeholder">
<?php
// $casesResult = $aca->exec( array( 'opcode' => 'generic_cases_view', 'case_ids' => $randomCasesResult['case_ids'] ) );
# sdv_debug('portfolio random full cases',$casesResult);
// echo $casesResult['html'];
/**
 * Performance optimization instead of 'generic_cases_view' call, which would call slow (due to NextGEN)
 * ApmComApi::getCaseView() with the same arguments as 'jazz_random_cases_view' again.
 */
foreach ( $randomCasesResult['cases'] as $case ) {
	apm_case( a2o( $case ) );
}
?>
                                        </div>
                                        <div class="popup__footer">

                                        </div>

                                        </div><!-- /popup -->

                                    </div>
                                </li>
                            </ul>

                        </div>


                    </div>
                </li>
                <li class="jazz__tower__floor jazz__tower__floor_blog" id="blog">
                    <div class="jazz__tower__floor__content">
<?php
?>
                        <div class="jazz-blog">
                            <div class="jazz-blog__head">
                                <div class="jazz-blog__head__title">
                                    <div class="jazz-title">Блог</div>
                                </div>
                                <div class="jazz-blog__head__bubble">
                                    <i class="jazz-bubble jazz-bubble_blog"></i>
                                </div>
                            </div>
<?php
// require_once( ABSPATH . '/templates/mockups/jazz_random_posts_mockup.php' );
$randomPostsResult = $aca->exec( array( 'opcode' => 'jazz_blog_header_gallery_view' ) );
# sdv_debug('random posts',$randomPostsResult);
echo $randomPostsResult['html'];
?>
                            <div data-current-page="1" class="pagination_placeholder" id="jazz-blog__posts__list_placeholder">
<?php
// require_once( ABSPATH . '/templates/mockups/jazz_blog_posts_mockup.php' );
$blogPostsResult = $aca->exec( array( 'opcode' => 'jazz_blog_posts_view' ) );
# sdv_debug('blog posts list',$blogPostsResult);
echo $blogPostsResult['html'];
?>
                            </div>
                            <div class="popup" id="news">
<?php
popup__bar();
?>
                                <div class="popup__body" id="jazz-blog__posts_placeholder">
<?php
$postsResult = $aca->exec( array( 'opcode' => 'generic_posts_view', 'post_ids' => $blogPostsResult['post_ids'] ) );
# sdv_debug('blog posts full view',$postsResult);
echo $postsResult['html'];
?>
                                </div>
                                <div class="popup__footer">

                                </div>
                            </div><!-- /popup -->

                            <div class="jazz-blog__mic-top-piece"></div>
                        </div>
                    </div>
                </li>
                <li class="jazz__tower__floor jazz__tower__floor_contacts" id="contacts">
                    <div class="jazz__tower__floor__content">


                        <div class="jazz-contacts">
                            <div class="jazz-contacts__map">
                                <a class="jazz-contacts__map__preview" data-uniq="skin-map" data-popup="#map" href="#"></a>
                            </div>
                            <div class="jazz-contacts__content">
                                <div class="jazz-contacts__content__head">
                                    <div class="jazz-contacts__content__head__title">
                                        <div class="jazz-title jazz-title_larger">Контакты</div>
                                    </div><!--
                                 --><div class="jazz-contacts__content__head__bubble">
                                        <i class="jazz-bubble jazz-bubble_contacts"></i>
                                    </div>
                                </div>
                                <div class="jazz-contacts__content__body">
                                    115054, г. Москва, ул. Дубининская, д. 57, стр. 2, оф. 2104<br>
                                    Телефон: + 7 (495) 662 57 01<br>
                                    <a class="jazz-mailto" href="jazz-mailto:info@apmcom.ru">
                                        <i class="jazz-mailto__icon"></i><!--
                                     --><div class="jazz-mailto__label">info@apmcom.ru</div>
                                    </a>
                                </div>

                                <div class="jazz-contacts__content__foot">
                                    <div class="fb-like" data-href="" data-layout="button_count" data-action="like" data-show-faces="true" data-share="false"></div>
                                </div>


                            </div>
                        </div>


                       <style>
                            /*
                                skin-map
                                background::      #a50034
                                gradient-start::  #d2004e
                                gradient-finish:: #9c002e
                            */
                            .popup[data-skin="skin-map"] .popup__bar,
                            .popup[data-skin="skin-map"] .popup__footer,
                            .popup[data-skin="skin-map"] .popup__bar__menu__item__link
                            {
                                background: #ca0049;
                            }

                            .popup[data-skin="skin-map"] .popup__bar__menu__item__link:hover
                            {
                                background: #ca0049;
                            }

                            .popup[data-skin="skin-map"] .popup__bar__menu__item__link:active
                            {
                                background: #ca0049;
                            }
                        </style>

                        <div class="popup" id="map">
                            <div class="popup__bar">
                                <menu class="popup__bar__menu">
                                    <li class="popup__bar__menu__item">
                                        <a class="popup__bar__menu__item__link popup__bar__menu__item__link_close" href="#">
                                            <i class="popup__bar__menu__item__link__icon"></i>
                                        </a>
                                    </li>
                                </menu>
                            </div>
                            <div class="popup__body">
                                <div class="popup__body__item" data-skin="skin-map">
                                    <script type="text/javascript" charset="utf-8" src="//api-maps.yandex.ru/services/constructor/1.0/js/?sid=_jqYsVBHpo5sVMVJXDgN3WukSIOnPEWx&width=780&height=480"></script>
                                </div>
                            </div>
                            <div class="popup__footer">

                            </div>
                        </div><!-- /popup -->

                    </div>
                    <div class="jazz__tower__floor__footer">© 2014 Active Project Manager</div>
                </li>
            </ul>

            <ul class="jazz__nav">
                <li class="jazz__nav__item">
                    <a class="jazz__nav__item__link jazz__nav__item__link_up" href="#up">
                        <i class="jazz__nav__item__link__arrow"></i>
                    </a>
                </li>
                <li class="jazz__nav__item">
                    <a class="jazz__nav__item__link jazz__nav__item__link_about" href="#about">
                        <span class="jazz__nav__item__link__label">О нас</span>
                        <i class="jazz__nav__item__link__icon"></i>
                    </a>
                </li>
                <li class="jazz__nav__item">
                    <a class="jazz__nav__item__link jazz__nav__item__link_service" href="#service">
                        <span class="jazz__nav__item__link__label">Услуги</span>
                        <i class="jazz__nav__item__link__icon"></i>
                    </a>
                </li>
                <li class="jazz__nav__item">
                    <a class="jazz__nav__item__link jazz__nav__item__link_portfolio" href="#portfolio">
                        <span class="jazz__nav__item__link__label">Портфолио</span>
                        <i class="jazz__nav__item__link__icon"></i>
                    </a>
                </li>
                <li class="jazz__nav__item">
                    <a class="jazz__nav__item__link jazz__nav__item__link_blog" href="#blog">
                        <span class="jazz__nav__item__link__label">Блог</span>
                        <i class="jazz__nav__item__link__icon"></i>
                    </a>
                </li>
                <li class="jazz__nav__item">
                    <a class="jazz__nav__item__link jazz__nav__item__link_contacts" href="#contacts">
                        <span class="jazz__nav__item__link__label">Контакты</span>
                        <i class="jazz__nav__item__link__icon"></i>
                    </a>
                </li>
                <li class="jazz__nav__item">
                    <a class="jazz__nav__item__link jazz__nav__item__link_down" href="#down">
                        <i class="jazz__nav__item__link__arrow"></i>
                    </a>
                </li>
            </ul>
        </div>

        <div class="overlay"></div>

        <script>

            $(document).on('ready', function(){
                window.location.hash = '#' + 'about';
                $('.jazz__nav__item__link[href="#about"]').addClass('jazz__nav__item__link_current');

                function currentFloor(){
                    var scrolled = $(window).scrollTop();
                    var current;

                    $('.jazz__tower__floor').each(function(){
                        if ( scrolled >= $(this).offset().top - $(this).height()/2 ) {
                            current = $(this).attr('id');
                        }
                    })

                    if ( window.location.hash != '#' + current ) {
                        history.pushState(null, null, '#' + current);
                        /*
                         * old solution:
                         * window.location.hash = '#' + current;
                         * nut this makes page jump
                         *
                         * new solution found here:
                         * http://lea.verou.me/2011/05/change-url-hash-without-page-jump/
                         */
                    }

                    $('.jazz__nav__item__link').removeClass('jazz__nav__item__link_current');
                    $('.jazz__nav__item__link[href="#'+current+'"]').addClass('jazz__nav__item__link_current');

                }

                $(window).on('scroll', currentFloor );
                $(window).on('ready', currentFloor );

                function scrollToAnchor( anchor ){
                    $('html,body').animate(
                        { scrollTop:$(anchor).offset().top },
                        500,
                        function(){ window.location.hash = anchor; }
                    );
                }


                $('.jazz__nav__item__link').on('click', function(event){
                    event.preventDefault();
                    var anchor = this.hash;

                    if ( anchor != '#up' && this.hash != '#down' ) {
                        scrollToAnchor(anchor);
                    }

                    if ( anchor == '#up' ) {
                        var current = window.location.hash;
                        if( $(current).prev().length ) {
                            scrollToAnchor ( '#' + $(current).prev().attr('id') );
                        }
                    }

                    if ( anchor == '#down' ) {
                        var current = window.location.hash;
                        if( $(current).next().length ) {
                            scrollToAnchor ( '#' + $(current).next().attr('id') );
                        }
                    }

                });

            });

        </script>

    </body>
</html>
<?php
} catch ( Exception $e ) {
	$aca->outputJsLog( $e );
}

