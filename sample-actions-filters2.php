<?php

/**
 * @package SanTechTriumph
 * @version 0.10
 */
/*
Plugin Name: SanTechTriumph
Plugin URI: http://www.mediawiki.org/wiki/User:QuestPC
Description: SanTechTriumph site customization code.
Author: Dmitriy Sintsov
Version: 0.10
Author URI: http://www.mediawiki.org/wiki/User:QuestPC
*/

require_once( dirname( __FILE__ ) . '/AbstractActionsFilters.php' );

/**
 * SanTechTriumph filters and actions for current site.
 */

class SanTechTriumph extends AbstractActionsFilters {

	const Version = '0.10';

	// protected static $remove_menu_items = array( 5, 15 );

	protected static $gettext_overrides = array(
		'default' => array(
			"Your site&#8217;s most recent comments." =>
			'Последние вопросы на сайте',
			"Recent Comments" =>
			'Последние вопросы',
			"You must be <a href=\"%s\">logged in</a> to post a comment." =>
			"Нужно <a href=\"%s\">зайти на сайт</a>, чтобы задать вопрос.",
			"Post Comment" =>
			"Задать вопрос",
			"Recent Comments" =>
			"Последние вопросы",
			"Add comment" =>
			"Задать вопрос",
			"Add new Comment" =>
			"Задайте вопрос",
			"Add Comment" =>
			"Задать вопрос",
			"Leave a Comment" =>
			"Задать вопрос",
			"Leave a Reply" =>
			"Задать вопрос",
			"Leave a Reply to %s" =>
			"Добавить вопрос для %s",
			"Say It!" =>
			"Задать вопрос",
		),
		'exclude-pages' => array(
			"Exclude Pages" => "Исключение страниц",
			"Include this page in lists of pages" => "Добавить данную страницу в меню сайта",
		),
		'santechtriumph' => array(
			#: 404.php:15
			"Oops! That page can&rsquo;t be found." =>
			"Страница не найдена.",

			#: 404.php:19
			"It looks like nothing was found at this location. Maybe try one of the links below or a search?" =>
			"По данному адресу нет информации. Воспользуйтесь ссылками ниже.",

			#: 404.php:27
			"Most Used Categories" =>
			"Популярные категории",

			#. translators: %1$s: smiley
			#: 404.php:44
			"Try looking in the monthly archives. %1\$s" =>
			"Просмотрите архивы по месяцам. %1\$s",

			#: archive.php:31
			"Author: %s" =>
			"Автор: %s",

			#: archive.php:39
			"Day: %s" =>
			"День: %s",

			#: archive.php:42
			"Month: %s" =>
			"Месяц: %s",

			#: archive.php:42
			# msgctxt "monthly archives date format"
			# "F Y"
			# ""

			#: archive.php:45
			"Year: %s" =>
			"Год: %s",

			#: archive.php:45
			# msgctxt "yearly archives date format"
			# "Y"
			# ""

			#: archive.php:48
			"Asides" =>
			"помимо",

			#: archive.php:51
			"Galleries" =>
			"Галереи",

			#: archive.php:54
			"Images" =>
			"Изображения",

			#: archive.php:57
			"Videos" =>
			"Видеофайлы",

			#: archive.php:60
			"Quotes" =>
			"Цитаты",

			#: archive.php:63
			"Links" =>
			"Ссылки",

			#: archive.php:66
			"Statuses" =>
			"Статусы",

			#: archive.php:69
			"Audios" =>
			"Звуковые файлы",

			#: archive.php:72
			"Chats" =>
			"Чаты",

			#: archive.php:75 sidebar.php:17
			"Archives" =>
			"Архивы",

			#: comments.php:30
			# msgctxt "comments title"
			"One thought on &ldquo;%2\$s&rdquo;" =>
			"Размышления о &ldquo;%2\$s&rdquo;",
			"%1\$s thoughts on &ldquo;%2\$s&rdquo;" =>
			"%1\$s размышления о &ldquo;%2\$s&rdquo;",

			#: comments.php:37 comments.php:57
			"Comment navigation" =>
			"Просмотр комментариев",

			#: comments.php:38 comments.php:58
			"&larr; Older Comments" =>
			"&larr; Предыдущие комментарии",

			#: comments.php:39 comments.php:59
			"Newer Comments &rarr;" =>
			"Следующие комментарии &rarr;",

			#: comments.php:69
			"Comments are closed." =>
			"Комментарии не требуются.",

			#: content-none.php:13
			"Nothing Found" =>
			"Ничего не найдено",

			#: content-none.php:19
			"Ready to publish your first post? <a href=\"%1\$s\">Get started here</a>." =>
			"Готовы опубликовать отчет о проделанной работе? <a href=\"%1\$s\">Начните здесь</a>.",

			#: content-none.php:23
			"Sorry, but nothing matched your search terms. Please try again with some different keywords." =>
			"По вашему запросу ничего не найдено. Попробуйте другие слова.",

			#: content-none.php:28
			"It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help." =>
			"Запрошенная информация отсутствует. Попробуйте поиск.",

			#: content-page.php:18 content-single.php:20 content.php:27
			"Pages:" =>
			"Страниц:",

			#: content-page.php:23 content-single.php:60 content.php:61
			#: inc/template-tags.php:81 inc/template-tags.php:100
			"Edit" =>
			"Редактировать",

			#. translators: used between list items, there is a space after the comma
			#: content-single.php:29 content-single.php:32 content.php:38 content.php:48
			# ", "
			# ""

			#: content-single.php:37
			"This entry was tagged %2\$s. Bookmark the <a href=\"%3\$s\" rel=\"bookmark\">permalink</a>." =>
			"Тэги записи: %2\$s. <a href=\"%3\$s\" rel=\"bookmark\">Постоянная ссылка</a>.",

			#: content-single.php:39
			"Bookmark the <a href=\"%3\$s\" rel=\"bookmark\">permalink</a>." =>
			"Сохранить <a href=\"%3\$s\" rel=\"bookmark\">постоянную ссылку</a>.",

			#: content-single.php:45
			"This entry was posted in %1\$s and tagged %2\$s. Bookmark the <a href=\"%3\$s\" rel=\"bookmark\">permalink</a>." =>
			"Запись %1\$s тэги %2\$s. <a href=\"%3\$s\" rel=\"bookmark\">Постоянная ссылка</a>.",

			#: content-single.php:47
			"This entry was posted in %1\$s. Bookmark the <a href=\"%3\$s\" rel=\"bookmark\">permalink</a>." =>
			"Запись %1\$s. <a href=\"%3\$s\" rel=\"bookmark\">Постоянная ссылка</a>.",

			#: content.php:24
			"Continue reading <span class=\"meta-nav\">&rarr;</span>" =>
			"Читать дальше <span class=\"meta-nav\">&rarr;</span>",

			#: content.php:42
			"Posted in %1\$s" =>
			"Опубликованно в %1\$s",

			#: content.php:52
			"Tagged %1\$s" =>
			"Тэги %1\$s",

			#: content.php:58
			"Leave a comment" =>
			"Оставить комментарий",

			#: content.php:58
			"1 Comment" =>
			"1 комментарий",

			#: content.php:58
			"% Comments" =>
			"% комментария",

			#: footer.php:16
			"Proudly powered by %s" =>
			" ",

			#: footer.php:18
			"Theme: %1\$s by %2\$s." =>
			" ",

			#: functions.php:45
			"Primary Menu" =>
			"Главное меню",

			#: functions.php:65
			"Sidebar" =>
			"Боковая полоса",

			#: header.php:31
			"Menu" =>
			"Меню",

			#: header.php:32
			"Skip to content" =>
			"Пропустить до содержимого",

			#: inc/extras.php:63
			"Page %s" =>
			"Страница %s",

			#: inc/template-tags.php:23
			"Posts navigation" =>
			"Навигация по работам",

			#: inc/template-tags.php:27
			"<span class=\"meta-nav\">&larr;</span> Older posts" =>
			"<span class=\"meta-nav\">&larr;</span> Предыдущие работы",

			#: inc/template-tags.php:31
			"Newer posts <span class=\"meta-nav\">&rarr;</span>" =>
			"Последующие работы <span class=\"meta-nav\">&rarr;</span>",

			#: inc/template-tags.php:56
			"Post navigation" =>
			"Навигация по работам",

			#: inc/template-tags.php:59
			# msgctxt "Previous post link"
			# "<span class=\"meta-nav\">&larr;</span> %title"
			# ""

			#: inc/template-tags.php:60
			# msgctxt "Next post link"
			# "%title <span class=\"meta-nav\">&rarr;</span>"
			# ""

			#: inc/template-tags.php:81
			"Pingback:" =>
			"Обратная ссылка:",

			#: inc/template-tags.php:91
			"%s <span class=\"says\">says:</span>" =>
			"%s <span class=\"says\">сообщает:</span>",

			#: inc/template-tags.php:97
			# msgctxt "1: date, 2: time"
			'%1\$s at %2\$s' =>
			'%1\$s, %2\$s',

			#: inc/template-tags.php:104
			"Your comment is awaiting moderation." =>
			"Ваш комментарий ожидает проверки модератором",

			#: inc/template-tags.php:145
			"<span class=\"posted-on\">Posted on %1\$s</span><span class=\"byline\"> by %2\$s</span>" =>
			"<span class=\"posted-on\">Опубликовано %1\$s</span><span class=\"byline\"> автором %2\$s</span>",

			#: search.php:16
			"Search Results for: %s" =>
			"Результаты поиска %s",

			#: searchform.php:10
			# msgctxt "label"
			"Search for:" =>
			"Искать:",

			#: searchform.php:11
			# msgctxt "placeholder"
			"Search &hellip;" =>
			"Поиск &hellip;",

			#: searchform.php:13
			# msgctxt "submit button"
			"Search" =>
			"Поиск",

			#: sidebar.php:24
			"Meta" =>
			"Мета",

			#. Theme Name of the plugin/theme
			# "_s"
			# ""

			#. Theme URI of the plugin/theme
			"http://underscores.me/" =>
			"http://www.mediawiki.org/wiki/User:QuestPC",

			#. Description of the plugin/theme
			"Hi. I'm a starter theme called <code>_s</code>, or <em>underscores</em>, if you like. I'm a theme meant for hacking so don't use me as a <em>Parent Theme</em>. Instead try turning me into the next, most awesome, WordPress theme out there. That's what I'm here for." =>
			"SanTechTriumph theme",

			#. Author of the plugin/theme
			# "Automattic"
			# ""

			#. Author URI of the plugin/theme
			# "http://automattic.com/"
			# ""

		),
	);

	protected static $post_type_labels = array(
		'post' => array(
			'name' => 'Работы',
			'singular_name' => 'Работа',
			'add_new' => 'Добавить новую работу',
			'add_new_item' => 'Добавить новую работу',
			'edit_item' => 'Редактировать работу',
			'new_item' => 'Новая работа',
			'view_item' => 'Просмотреть работу',
			'search_items' => 'Искать работы',
			'not_found' => 'Работы не найдены',
			'not_found_in_trash' => 'В корзине работы отсутствуют',
			'all_items' => 'Все работы',
			'menu_name' => 'Работы',
			'name_admin_bar' => 'Работа',
		)
	);

	/*
	public static function action_init() {
		parent::action_init();
	}
	*/

	/*
	public static function init() {
		parent::init();
		static::add_hooks( array(
		) );
	}
	*/

} /* end of SanTechTriumph class */

SanTechTriumph::init();

