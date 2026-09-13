<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2016-2026
 */

$enc = $this->encoder();


/** admin/jqadm/navbar
 * List of JQAdm client names shown in the navigation bar of the admin interface
 *
 * You can add, remove or reorder the links in the navigation bar by
 * setting a new list of client resource names.
 *
 * @param array List of resource client names
 * @since 2017.10
 * @see admin/jqadm/navbar-limit
 */
$navlist = map( $this->config( 'admin/jqadm/navbar', [] ) )->ksort();

foreach( $navlist as $key => $navitem )
{
	$name = is_array( $navitem ) ? ( $navitem['_'] ?? current( $navitem ) ) : $navitem;

	if( !$this->access( $this->config( 'admin/jqadm/resource/' . $name . '/groups', [] ) ) ) {
		$navlist->remove( $key );
	}
}


$resource = $this->param( 'resource', 'dashboard' );
$site = $this->param( 'site', 'default' );
$lang = $this->param( 'locale' );

$params = ['resource' => $resource, 'site' => $site];
$extParams = ['site' => $site];

if( $lang ) {
	$params['locale'] = $extParams['locale'] = $lang;
}


$pos = $navlist->pos( function( $item, $key ) use ( $resource ) {
	return is_array( $item ) ? in_array( $resource, $item ) : !strncmp( $resource, $item, strlen( $item ) );
} );
$before = $pos > 0 ? $navlist->slice( $pos - 1, 1 )->first() : null;
$before = is_array( $before ) ? $before['_'] ?? reset( $before ) : $before;
$after = $pos < count( $navlist ) ? $navlist->slice( $pos + 1, 1 )->first() : null;
$after = is_array( $after ) ? $after['_'] ?? reset( $after ) : $after;


?>
<div class="aimeos" lang="<?= $enc->attr( $this->param( 'locale' ) ) ?>"
	data-graphql="<?= $enc->attr( $this->link( 'admin/graphql/url', ['site' => $site, $this->csrf()->name() => $this->csrf()->value()] ) ) ?>"
	data-url="<?= $enc->attr( $this->link( 'admin/jsonadm/url/options', array( 'site' => $site ) ) ) ?>"
	data-user-siteid="<?= $enc->attr( $this->get( 'pageUserSiteid' ) ) ?>">

	<nav class="main-sidebar">
		<div class="sidebar-wrapper">

			<a class="logo" target="_blank" href="https://aimeos.org/update/?type=<?= $this->get( 'aimeosType' ) ?>&version=<?= $this->get( 'aimeosVersion' ) ?>">
				<img src="https://aimeos.org/check/?type=<?= $this->get( 'aimeosType' ) ?>&version=<?= $this->get( 'aimeosVersion' ) ?>&extensions=<?= $this->get( 'aimeosExtensions' ) ?>" alt="Aimeos update" title="Aimeos update">
			</a>

			<ul class="sidebar-menu">

				<?php /* The ASAAN shop runs as a single shop, so the "Site" popup is removed (no functional loss) */ ?>
				<li class="none <?= $before === null ? 'before' : '' ?>"></li>

				<?php foreach( $navlist as $nav => $navitem ) : ?>
					<?php if( is_array( $navitem ) ) : $nav = $navitem['_'] ?? current( $navitem ) ?>

						<li class="treeview menuitem-<?= $enc->attr( $nav ) ?> <?= $nav === $before ? 'before' : '' ?> <?= in_array( $resource, $navitem ) !== false ? 'active' : '' ?> <?= $nav === $after ? 'after' : '' ?>">
							<span class="item-group">
								<i class="icon"></i>
								<span class="title"><?= $enc->attr( $this->translate( 'admin', $nav ) ) ?></span>
							</span>
							<div class="tree-menu-wrapper">
								<div class="menu-header">
									<a href="#"><?= $enc->html( $this->translate( 'admin', $nav ) ) ?></a>
									<span class="close"></span>
								</div>
								<ul class="tree-menu">

								<?php foreach( map( $navitem )->remove( '_' )->ksort() as $subresource ) : ?>
										<?php if( $this->access( $this->config( 'admin/jqadm/resource/' . $subresource . '/groups', [] ) ) ) : ?>
											<?php $key = $this->config( 'admin/jqadm/resource/' . $subresource . '/key', '' ) ?>

											<li class="menuitem-<?= str_replace( '/', '-', $subresource ) ?> <?= $subresource === $resource ? 'active' : '' ?>">
												<a class="item-group" href="<?= $enc->attr( $this->link( 'admin/jqadm/url/search', ['resource' => $subresource] + $params ) ) ?>"
													title="<?= $enc->attr( sprintf( $this->translate( 'admin', '%1$s (Ctrl+Alt+%2$s)' ), $this->translate( 'admin', $subresource ), $key ) ) ?>"
													data-ctrlkey="<?= $enc->attr( strtolower( $key ) ) ?>">
													<i class="icon"></i>
													<span class="name"><?= $enc->html( $this->translate( 'admin', $subresource ) ) ?></span>
												</a>
											</li>

										<?php endif ?>
									<?php endforeach ?>
								</ul>
							</div>
						</li>

					<?php else : ?>
						<?php $key = $this->config( 'admin/jqadm/resource/' . $navitem . '/key', '' ) ?>

						<li class="menuitem-<?= $enc->attr( $navitem ) ?> <?= $navitem === $before ? 'before' : '' ?> <?= !strncmp( $resource, $navitem, strlen( $navitem ) ) ? 'active' : '' ?> <?= $navitem === $after ? 'after' : '' ?>">
							<a class="item-group" href="<?= $enc->attr( $this->link( 'admin/jqadm/url/search', ['resource' => $navitem] + $params ) ) ?>"
								title="<?= $enc->attr( sprintf( $this->translate( 'admin', '%1$s (Ctrl+Alt+%2$s)' ), $this->translate( 'admin', $navitem ), $key ) ) ?>"
								data-ctrlkey="<?= $enc->attr( strtolower( $key ) ) ?>">
								<i class="icon"></i>
								<span class="title"><?= $enc->html( $this->translate( 'admin', $navitem ) ) ?></span>
							</a>
						</li>

					<?php endif ?>
				<?php endforeach ?>

				<li class="none"></li>
			</ul>

		</div>
	</nav>

	<main class="main-content">
		<?= $this->partial( $this->config( 'admin/jqadm/partial/info', 'info' ), [
			'info' => array_merge( $this->get( 'pageInfo', [] ), $this->get( 'info', [] ) ),
			'error' => $this->get( 'errors', [] )
		] ) ?>

		<?= $this->block()->get( 'jqadm_content' ) ?>
	</main>

	<footer class="main-footer">
		<a href="https://github.com/aimeos/ai-admin-jqadm/issues" target="_blank">
			<?= $enc->html( $this->translate( 'admin', 'Bug or suggestion?' ) ) ?>
		</a>
	</footer>

	<?= $this->partial( $this->config( 'admin/jqadm/partial/confirm', 'confirm' ) ) ?>
	<?= $this->partial( $this->config( 'admin/jqadm/partial/problem', 'problem' ) ) ?>

</div>