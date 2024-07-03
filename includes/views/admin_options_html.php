<h3><?php echo __( 'IceNox Pay Payment Settings', 'woocommerce-icenox-pay-plugin' ); ?></h3>
<div id="poststuff">
    <div id="post-body" class="metabox-holder columns-2">
        <div id="post-body-content">
            <table class="form-table">
				<?php $this->generate_settings_html(); ?>
            </table>
        </div>
        <div id="postbox-container-1" class="postbox-container">
            <div id="side-sortables" class="meta-box-sortables ui-sortable">
                <div class="postbox " id="icenox-support">
                    <h3><?php echo __( 'Need Help?', 'woocommerce-icenox-pay-plugin' ); ?></h3>
                    <div class="inside">
                        <div class="support-widget">
                            <p>
                                <br/>
                                <img style="width:100%;" src="https://pay.icenox.com/static/images/logo-dark.svg" alt="IceNox Pay">
                                <br/>
                                <br/>
                                <br/>
								<?php echo __( 'Contact our Merchant Support, if you need help configuring the plugin.', 'woocommerce-icenox-pay-plugin' ); ?>
                            </p>
                            <ul>
                                <li>» <a href="mailto:info@icenox.com"
                                         target="_blank"><?php echo __( 'Email Us', 'woocommerce-icenox-pay-plugin' ); ?></a>
                                </li>
                            </ul>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<div class="clear"></div>