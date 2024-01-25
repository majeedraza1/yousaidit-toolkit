<?php
add_action( 'wp_footer', function () {
	?>
    <script type="text/javascript">
        const tabs = document.querySelector('.woocommerce-tabs');
        if (tabs) {
            const panels = tabs.querySelectorAll('.woocommerce-Tabs-panel');
            window.addEventListener("load", () => {
                const panel = tabs.querySelector('.woocommerce-Tabs-panel');
                if (panel) {
                    panel.style.display = '';
                }
            });
            const anchors = tabs.querySelectorAll('a');
            anchors.forEach(anchor => {
                anchor.addEventListener('click', (event) => {
                    const tabSelector = event.target.getAttribute('href').replace('#', '');
                    panels.forEach(panel => {
                        if (panel.getAttribute('id') === tabSelector && 'none' === panel.style.display) {
                            panel.style.display = '';
                        } else {
                            panel.style.display = "none";
                        }
                    })
                });
            })
        }
    </script>
	<?php
} );
