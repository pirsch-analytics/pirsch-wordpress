<?php
function pirsch_analytics_snippet() {
    $ic = get_option('pirsch_analytics_identification_code');

    if (!empty($ic) && !empty(get_option('pirsch_analytics_add_script'))) {
        ?>
        <script defer src="https://api.pirsch.io/pa.js"
            id="pianjs"
            data-code="<?php echo $ic; ?>"
            <?php if (!empty(get_option('pirsch_analytics_script_disable_page_views'))) {echo 'data-disable-page-views';} ?>
            ></script>
        <?php
    }
}
