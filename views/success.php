<div class="wrap">
    <h2>Skeleton Builder</h2>
    <div class="updated">
        <p>Skeleton has been built.</p>
        <?php if( $skeleton_menu != '' )
            printf(
                '<p>A menu (%s) has been created based on your skeleton.</p>',
                $menu_id !== false ? sprintf('<a href="nav-menus.php?action=edit&menu=%d">%s</a>', $menu_id, $skeleton_menu ) : $skeleton_menu
            );
        ?>
    </div>
    <p class="pre"><?php echo $skeleton; ?></p>
</div>
