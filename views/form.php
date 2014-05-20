<?php
// @todo: Maybe add tooltips on things - http://wordpress.stackexchange.com/questions/46028/wordpress-admin-tooltip-hooks
?>
<div class="wrap">
    <h2 id="skeleton-builder-header" class="nav-tab-wrapper">Skeleton Builder</h2>
    <noscript>
        <div class="error">
            <p>
                <strong>You have JavaScript disabled</strong>. This plugin requires JavaScript. Please enable it now.
            </p>
        </div>
        <div class="manage-menus">
            <p>
                <strong>Since you have JavaScript disabled, you will need to use the following skeleton format.</strong>
            </p>
            <p>
                The skeleton is a series of slug:title pairs (one per line). If the title is missing, the capitalized slug will be used as the page title. You can use the dash (-) before the slug to indicate depth.
            </p>
            <p><strong>Example Skeleton - Using Dashes</strong></p>
            <p>
                home<br />
                about<br />
                -staff<br />
                --jobs<br />
                -our-office:View Our Office<br />
                contact:Contact Us
            </p>
        </div>
    </noscript>
    <form method="post" action="">

        <div class="manage-menus">

            <div class="alignleft post-type-selection tooltip" title="What post type do you want to use for your skeleton?">
                <label for="skeleton_post_types">Post Type</label>
                <select name="skeleton_post_type" id="skeleton_post_types" required>
                    <optgroup label="Select a post type">
                    <?php
                        foreach ($skeleton_post_types as $pt => $label) {
                            $name = $label['name'];
                            if( ! $label['hierarchical'] )
                                $name .= ' (not hierarchical)';
                            printf('<option value="%1$s" data-hierarchical="%3$s" %4$s>%2$s</option>', $pt, $name, $label['hierarchical'] ? 'true' : 'false', selected( $skeleton_post_type, $pt, false ) );
                        }
                    ?>
                    </optgroup>
                </select>
            </div>

            <a class="alignright button-secondary" href="#contextual-help-wrap" onclick="jQuery('#contextual-help-link').click(); return false;">Instructions &amp; Skeleton Examples</a>
        </div>

        <div id="skeleton-builder-frame" class="clearfix">

            <fieldset id="skeleton-input" class="stuffbox">
                <h3>Skeleton</h3>
                <?php wp_nonce_field('build-skeleton','skeleton_builder_action'); ?>
                <textarea rows="16" cols="150" name="skeleton" id="skeleton" required tabindex="1" placeholder="Enter your skeleton here"><?php echo $skeleton; ?></textarea>
                <div class="mask">
                    <div class="bg"></div>
                    <div class="msg">
                        <p>The outline &amp; post types have been locked for editing.</p>
                        <p><small>By unlocking the outline &amp; post types any changes that have been made to the menu will be lost.</small></p>
                        <button type="button" class="unlock-button button-secondary"><span class="unlock-button-text">Unlock</span></button>
                    </div>
                </div>
            </fieldset>

            <div id="menu-management-liquid">

                <div id="menu-management">

                    <div class="menu-edit">

                        <div id="nav-menu-header">
                            <div class="major-publishing-actions clearfix">

                                <button type="submit" class="button-primary alignright build-skeleton-button" tabindex="3"><?php _e('Build Skeleton'); ?></button>

                                <label class="menu-name-label howto alignleft" for="skeleton_menu" title="Do you want a menu automatically created based on your skeleton? If so, please enter a menu name below.">
                                    <span>Menu Name</span>
                                    <input id="menu-name" class="menu-name regular-text menu-item-textbox" type="text" value="<?php echo $skeleton_menu; ?>" title="Enter menu name here" placeholder="Enter a name for this menu &mdash; optional" name="skeleton_menu" />
                                </label>

                            </div>
                        </div>

                        <div id="skeleton-drag-area-frame">

                            <p>
                                You can drag and drop the skeleton below to make any adjustments you need. You can expand each item to specify label, slug, title attribute, and CSS class.
                            </p>
                            <noscript>
                                <p>
                                    <strong>JavaScript is required to use this feature.</strong>
                                </p>
                            </noscript>

                            <div id="skeleton-drag-area">

                            </div>

                        </div>

                        <div id="nav-menu-footer">
                            <div class="major-publishing-actions clearfix">
                                <button type="submit" class="button-primary alignright build-skeleton-button"><?php _e('Build Skeleton'); ?></button>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>
