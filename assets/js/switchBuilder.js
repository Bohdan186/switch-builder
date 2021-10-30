jQuery(document).ready( function ($) {
	let ajaxurl = '/wp-admin/admin-ajax.php';
	let $switchBuilderButton = $('.sb-button');

	function getWillActivatedBuilder( $this ) {
		if ( $this.hasClass('wpbakery') ) {
			return 'ELEMENTOR';
		}else if ( $this.hasClass('elementor') ) {
			return 'WPBakery';
		}else {
			return false;
		}
	}

	$switchBuilderButton.on( 'click', function( event ){
		event.preventDefault();
		$this = $(this);
		$button = $this.find('a.ab-item');

		$button.addClass('sb-loading');

		$.ajax({
			url: ajaxurl,
			method: 'post',
			data: {
				action: 'switch-builder',
			},
			success: function() {
				let message = '';

				$button.removeClass('sb-loading');

				if ( false !== getWillActivatedBuilder($this) ) {
					message = `<span class="sb-activeBuilder">${getWillActivatedBuilder( $this )}</span> activated !`;
				} else {
					message = 'Switched';
				}

				$this.append(`<p class="sb-message">${message}</p>`)

				$this.find('.sb-message').animate({
					marginLeft: "100%",
					opacity: "100%",
				}, 1000);
					
				setTimeout(function() {
					$('.sb-message').remove();
				}, 2000);

				if ( $this.hasClass('wpbakery') ) {
					$this.removeClass('wpbakery');
					$this.addClass('elementor');
				} else if ( $this.hasClass('elementor') ) {
					$this.removeClass('elementor');
					$this.addClass('wpbakery');
				}
			},
		});
	});
});