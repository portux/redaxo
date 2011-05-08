<?php

/**
 *
 * @package redaxo5
 * @version svn:$Id$
 */

$info = '';
$warning = '';

if ($func == 'setup')
{
  // REACTIVATE SETUP

  $master_file = rex_path::src('config/master.inc.php');
  $cont = rex_file::get($master_file);
  $cont = preg_replace("@(REX\['SETUP'\].?\=.?)[^;]*@", '$1true', $cont);
  // echo nl2br(htmlspecialchars($cont));
  if (rex_file::put($master_file, $cont) !== false)
  {
    $info = rex_i18n::msg('setup_error1', '<a href="index.php">', '</a>');
  }
  else
  {
    $warning = rex_i18n::msg('setup_error2');
  }
}elseif ($func == 'generate')
{
  // generate all articles,cats,templates,caches
  $info = rex_generateAll();
}
elseif ($func == 'updateinfos')
{
  $neu_startartikel       = rex_post('neu_startartikel', 'int');
  $neu_notfoundartikel    = rex_post('neu_notfoundartikel', 'int');
  $neu_defaulttemplateid  = rex_post('neu_defaulttemplateid', 'int');
  $neu_lang               = rex_post('neu_lang', 'string');
  // ' darf nichtg escaped werden, da in der Datei der Schlüssel nur zwischen " steht
  $neu_error_emailaddress = str_replace("\'", "'", rex_post('neu_error_emailaddress', 'string'));
  $neu_SERVER             = str_replace("\'", "'", rex_post('neu_SERVER', 'string'));
  $neu_SERVERNAME         = str_replace("\'", "'", rex_post('neu_SERVERNAME', 'string'));
  $neu_modrewrite         = rex_post('neu_modrewrite', 'string');

  $startArt = rex_ooArticle::getArticleById($neu_startartikel);
  $notFoundArt = rex_ooArticle::getArticleById($neu_notfoundartikel);

  $REX['LANG'] = $neu_lang;
  $master_file = rex_path::src('config/master.inc.php');
  $cont = rex_file::get($master_file);

  if(!rex_ooArticle::isValid($startArt))
  {
    $warning .= rex_i18n::msg('settings_invalid_sitestart_article')."<br />";
  }else
  {
    $cont = preg_replace("@(REX\['START_ARTICLE_ID'\].?\=.?)[^;]*@", '${1}'.strtolower($neu_startartikel), $cont);
    $REX['START_ARTICLE_ID'] = $neu_startartikel;
  }

  if(!rex_ooArticle::isValid($notFoundArt))
  {
    $warning .= rex_i18n::msg('settings_invalid_notfound_article')."<br />";
  }else
  {
	  $cont = preg_replace("@(REX\['NOTFOUND_ARTICLE_ID'\].?\=.?)[^;]*@", '${1}'.strtolower($neu_notfoundartikel), $cont);
    $REX['NOTFOUND_ARTICLE_ID'] = $neu_notfoundartikel;
  }

  $sql = rex_sql::factory();
  $sql->setQuery('SELECT * FROM '. $REX['TABLE_PREFIX'] .'template WHERE id='. $neu_defaulttemplateid .' AND active=1');
  if($sql->getRows() != 1 && $neu_defaulttemplateid != 0)
  {
    $warning .= rex_i18n::msg('settings_invalid_default_template')."<br />";
  }else
	{
	  $cont = preg_replace("@(REX\['DEFAULT_TEMPLATE_ID'\].?\=.?)[^;]*@", '${1}'.strtolower($neu_defaulttemplateid), $cont);
    $REX['DEFAULT_TEMPLATE_ID'] = $neu_defaulttemplateid;
	}

  $cont = preg_replace("@(REX\['ERROR_EMAIL'\].?\=.?)[^;]*@", '$1"'.strtolower($neu_error_emailaddress).'"', $cont);
  $cont = preg_replace("@(REX\['LANG'\].?\=.?)[^;]*@", '$1"'.$neu_lang.'"', $cont);
  $cont = preg_replace("@(REX\['SERVER'\].?\=.?)[^;]*@", '$1"'. ($neu_SERVER).'"', $cont);
  $cont = preg_replace("@(REX\['SERVERNAME'\].?\=.?)[^;]*@", '$1"'. ($neu_SERVERNAME).'"', $cont);
  $cont = preg_replace("@(REX\['MOD_REWRITE'\].?\=.?)[^;]*@",'$1'.strtolower($neu_modrewrite),$cont);

  if($warning == '')
  {
    if(rex_file::put($master_file, $cont) > 0)
    {
      $info = rex_i18n::msg('info_updated');

      // Zuweisungen für Wiederanzeige
      $REX['MOD_REWRITE'] = $neu_modrewrite === 'TRUE';
      $REX['ERROR_EMAIL'] = $neu_error_emailaddress;
      $REX['SERVER'] = $neu_SERVER;
      $REX['SERVERNAME'] = $neu_SERVERNAME;
    }
  }
}

$sel_template = new rex_select();
$sel_template->setStyle('class="rex-form-select"');
$sel_template->setName('neu_defaulttemplateid');
$sel_template->setId('rex-form-default-template-id');
$sel_template->setSize(1);
$sel_template->setSelected($REX['DEFAULT_TEMPLATE_ID']);

$templates = rex_ooCategory::getTemplates(0);
if (empty($templates))
  $sel_template->addOption(rex_i18n::msg('option_no_template'), 0);
else
  $sel_template->addArrayOptions($templates);

$sel_lang = new rex_select();
$sel_lang->setStyle('class="rex-form-select"');
$sel_lang->setName('neu_lang');
$sel_lang->setId('rex-form-lang');
$sel_lang->setSize(1);
$sel_lang->setSelected($REX['LANG']);

foreach (rex_i18n::getLocales() as $l)
{
  $sel_lang->addOption($l, $l);
}

$sel_mod_rewrite = new rex_select();
$sel_mod_rewrite->setSize(1);
$sel_mod_rewrite->setStyle('class="rex-form-select"');
$sel_mod_rewrite->setName('neu_modrewrite');
$sel_mod_rewrite->setId('rex-form-mod-rewrite');
$sel_mod_rewrite->setSelected($REX['MOD_REWRITE'] === false ? 'FALSE' : 'TRUE');

$sel_mod_rewrite->addOption('TRUE', 'TRUE');
$sel_mod_rewrite->addOption('FALSE', 'FALSE');

if ($warning != '')
  echo rex_warning($warning);

if ($info != '')
  echo rex_info($info);


?>
  <div class="rex-form" id="rex-form-system-setup">
  	<form action="index.php" method="post">
    	<input type="hidden" name="page" value="specials" />
    	<input type="hidden" name="func" value="updateinfos" />

			<div class="rex-area-col-2">
				<div class="rex-area-col-a">

					<h3 class="rex-hl2"><?php echo rex_i18n::msg("specials_features"); ?></h3>

					<div class="rex-area-content">
						<h4 class="rex-hl3"><?php echo rex_i18n::msg("delete_cache"); ?></h4>
						<p class="rex-tx1"><?php echo rex_i18n::msg("delete_cache_description"); ?></p>
						<p class="rex-button"><a class="rex-button" href="index.php?page=specials&amp;func=generate"><span><span><?php echo rex_i18n::msg("delete_cache"); ?></span></span></a></p>

						<h4 class="rex-hl3"><?php echo rex_i18n::msg("setup"); ?></h4>
						<p class="rex-tx1"><?php echo rex_i18n::msg("setup_text"); ?></p>
						<p class="rex-button"><a class="rex-button" href="index.php?page=specials&amp;func=setup" onclick="return confirm('<?php echo rex_i18n::msg("setup"); ?>?');"><span><span><?php echo rex_i18n::msg("setup"); ?></span></span></a></p>

            <h4 class="rex-hl3"><?php echo rex_i18n::msg("version"); ?></h4>
            <p class="rex-tx1">
            REDAXO: <?php echo $REX['VERSION'].'.'.$REX['SUBVERSION'].'.'.$REX['MINORVERSION']; ?><br />
            PHP: <?php echo phpversion(); ?> (<a href="index.php?page=phpinfo">php_info</a>)</p>

            <h4 class="rex-hl3"><?php echo rex_i18n::msg("database"); ?></h4>
            <p class="rex-tx1">MySQL: <?php echo rex_sql::getServerVersion(); ?><br /><?php echo rex_i18n::msg("name"); ?>: <?php echo $REX['DB'][1]['name']; ?><br /><?php echo rex_i18n::msg("host"); ?>: <?php echo $REX['DB'][1]['host']; ?></p>
					</div>
				</div>

				<div class="rex-area-col-b">

					<h3 class="rex-hl2"><?php echo rex_i18n::msg("specials_settings"); ?></h3>

					<div class="rex-area-content">

						<fieldset class="rex-form-col-1">
							<legend><?php echo rex_i18n::msg("general_info_header"); ?></legend>

							<div class="rex-form-wrapper">

            <!--
							<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-read">
										<label for="rex-form-version">Version</label>
										<span class="rex-form-read" id="rex-form-version"><?php echo $REX['VERSION'].'.'.$REX['SUBVERSION'].'.'.$REX['MINORVERSION']; ?></span>
									</p>
								</div>
						-->

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-text">
										<label for="rex-form-server">$REX['SERVER']</label>
										<input class="rex-form-text" type="text" id="rex-form-server" name="neu_SERVER" value="<?php echo htmlspecialchars($REX['SERVER']); ?>" />
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-text">
										<label for="rex-form-servername">$REX['SERVERNAME']</label>
										<input class="rex-form-text" type="text" id="rex-form-servername" name="neu_SERVERNAME" value="<?php echo htmlspecialchars($REX['SERVERNAME']); ?>" />
									</p>
								</div>
							</div>
            <!--
						</fieldset>
						-->

						<!--
						<fieldset class="rex-form-col-1">
							<legend><?php echo rex_i18n::msg("db1_can_only_be_changed_by_setup"); ?></legend>

							<div class="rex-form-wrapper">

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-read">
										<label for="rex-form-db-host">$REX[\'DB\'][\'1\'][\'HOST\']</label>
										<span class="rex-form-read" id="rex-form-db-host">&quot;<?php echo $REX['DB'][1]['host']; ?>&quot;</span>
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-text">
										<label for="rex-form-db-login">$REX[\'DB\'][\'1\'][\'LOGIN\']</label>
										<span id="rex-form-db-login">&quot;<?php echo $REX['DB'][1]['login']; ?>&quot;</span>
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-read">
										<label for="rex-form-db-psw">$REX[\'DB\'][\'1\'][\'PSW\']</label>
										<span class="rex-form-read" id="rex-form-db-psw">&quot;****&quot;</span>
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-read">
										<label for="rex-form-db-name">$REX[\'DB\'][\'1\'][\'NAME\']</label>
										<span class="rex-form-read" id="rex-form-db-name">&quot;<?php echo htmlspecialchars($REX['DB'][1]['name']); ?>&quot;</span>
									</p>
								</div>
							</div>
						</fieldset>
						-->

						<!--
						<fieldset class="rex-form-col-1">
							<legend>'.rex_i18n::msg("specials_others").'</legend>

							<div class="rex-form-wrapper">
						-->

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-read">
										<label for="rex_src_path">rex_path::src()</label>
										<span class="rex-form-read" id="rex_src_path" title="'. rex_path::src() .'">&quot;
                  <?php
										$tmp = rex_path::src();
										if (strlen($tmp)>21)
											$tmp = substr($tmp,0,8)."..".substr($tmp,strlen($tmp)-13);

										echo $tmp;
								  ?>

					         &quot;</span>
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-text">
										<label for="rex-form-error-email">$REX['ERROR_EMAIL']</label>
										<input class="rex-form-text" type="text" id="rex-form-error-email" name="neu_error_emailaddress" value="<?php echo htmlspecialchars($REX['ERROR_EMAIL']); ?>" />
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-widget">
										<label for="rex-form-startarticle-id">$REX['START_ARTICLE_ID']</label>
										<?php echo rex_var_link::_getLinkButton('neu_startartikel', 1, $REX['START_ARTICLE_ID']); ?>
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-widget">
										<label for="rex-form-notfound-article-id">$REX['NOTFOUND_ARTICLE_ID']</label>
                    <?php echo rex_var_link::_getLinkButton('neu_notfoundartikel', 2, $REX['NOTFOUND_ARTICLE_ID']); ?>
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-select">
										<label for="rex-form-default-template-id">$REX['DEFAULT_TEMPLATE_ID']</label>
										<?php echo $sel_template->get(); ?>
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-select">
										<label for="rex-form-lang">$REX['LANG']</label>
										<?php echo $sel_lang->get(); ?>
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-select">
										<label for="rex-form-mod-rewrite">$REX['MOD_REWRITE']</label>
										<?php echo $sel_mod_rewrite->get(); ?>
									</p>
								</div>

								<div class="rex-form-row">
									<p class="rex-form-col-a rex-form-submit">
										<input type="submit" class="rex-form-submit" name="sendit" value="<?php echo rex_i18n::msg("specials_update"); ?>" <?php echo rex_accesskey(rex_i18n::msg('specials_update'), $REX['ACKEY']['SAVE']); ?> />
									</p>
								</div>

            <!--
								</div>
						-->
						</fieldset>
					</div> <!-- Ende rex-area-content //-->

				</div> <!-- Ende rex-area-col-b //-->
			</div> <!-- Ende rex-area-col-2 //-->

		</form>
	</div>