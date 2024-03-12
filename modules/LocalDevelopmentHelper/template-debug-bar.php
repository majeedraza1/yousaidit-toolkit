<?php
/**
 * @type string $card_type Card type
 * @type string $ajax_url Ajax url
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="bg-white bottom-4 left-0 border border-solid border-gray-300 flex justify-center items-center"
     style="z-index: 99;position: fixed">
    <label>
        <input type="checkbox" class="mb-0" value="customized" <?php checked( 'customized', $card_type ) ?>
               id="show-customized-card-product"/>
        <span>Customizable product</span>
    </label>
</div>
<script type="text/javascript">
    const checkboxShowCustomizedCard = document.querySelector('#show-customized-card-product');
    if (checkboxShowCustomizedCard) {
        checkboxShowCustomizedCard.addEventListener('change', async event => {
            const data = new FormData();
            data.append('card_type', event.target.checked ? event.target.value : '')
            // Create an XMLHttpRequest object
            fetch("<?php echo str_replace( 'amp;', '', $ajax_url ) ?>", {
                method: "POST",
                body: data,
            }).then(data => {
                console.log(data.json());
                window.location.reload();
            })
        })
    }
</script>