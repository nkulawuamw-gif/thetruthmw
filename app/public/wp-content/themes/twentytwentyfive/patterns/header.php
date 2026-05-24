<?php
/**
 * Title: Header
 * Slug: twentytwentyfive/header
 * Categories: header
 * Block Types: core/template-part/header
 * Description: News site header with top bar, navigation, search, and breaking news.
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"0","bottom":"0"}}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull has-base-background-color has-background">

	<!-- TOP BAR: Logo + Search + Navigation -->
	<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}},"border":{"bottom":{"color":"var:preset|color|accent-6","width":"1px"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group tts-top-bar" style="border-bottom-color:var(--wp--preset--color--accent-6);border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
		<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
		<div class="wp-block-group alignwide">
			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
			<div class="wp-block-group">
				<!-- wp:site-logo {"width":48,"shouldSyncIcon":true} /-->
				<!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.5rem"}}} /-->
			</div>
			<!-- /wp:group -->
			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
			<div class="wp-block-group">
				<!-- wp:search {"label":"<?php esc_html_e( 'Search', 'twentytwentyfive' ); ?>","showLabel":false,"placeholder":"<?php esc_html_e( 'Search news...', 'twentytwentyfive' ); ?>","width":360,"widthUnit":"px","buttonText":"<?php esc_html_e( 'Search', 'twentytwentyfive' ); ?>","buttonPosition":"button-inside","buttonUseIcon":true,"style":{"border":{"radius":"20px"}}} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
		<!-- HEADER ADS CAROUSEL -->
		<?php $tts_ads = TheTruthSettings::instance()->get_header_ads(); if (!empty($tts_ads)) : ?>
		<div class="tts-header-ad" onclick="ttsAdClick(event)">
			<div class="tts-header-ad-track">
				<?php foreach ($tts_ads as $ad) : ?>
				<div class="tts-header-ad-slide"><?php echo $ad; ?></div>
				<?php endforeach; ?>
				<?php foreach ($tts_ads as $ad) : ?>
				<div class="tts-header-ad-slide"><?php echo $ad; ?></div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
		<!-- /HEADER ADS CAROUSEL -->
		<!-- wp:navigation {"overlayBackgroundColor":"base","overlayTextColor":"contrast","style":{"spacing":{"blockGap":"var:preset|spacing|40","margin":{"top":"var:preset|spacing|20"}}},"layout":{"type":"flex","justifyContent":"left","flexWrap":"wrap"}} -->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Home', 'twentytwentyfive' ); ?>","url":"/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Politics', 'twentytwentyfive' ); ?>","url":"/category/politics/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Business', 'twentytwentyfive' ); ?>","url":"/category/business/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Technology', 'twentytwentyfive' ); ?>","url":"/category/technology/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Sports', 'twentytwentyfive' ); ?>","url":"/category/sports/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Entertainment', 'twentytwentyfive' ); ?>","url":"/category/entertainment/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Health', 'twentytwentyfive' ); ?>","url":"/category/health/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'World', 'twentytwentyfive' ); ?>","url":"/category/world/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->
	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
