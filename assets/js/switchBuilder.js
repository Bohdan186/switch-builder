(function($) {
	let ajaxurl = '/wp-admin/admin-ajax.php';

	function updateBuilderData($this) {
		let $sbButtonData  = $this.find('.sb-button-data');
		let $activeBuilder = $sbButtonData.data('active-builder');

		if ('wpbakery' === $activeBuilder) {
			$sbButtonData.data('active-builder', 'elementor');
		}else if ('elementor' === $activeBuilder) {
			$sbButtonData.data('active-builder', 'wpbakery');
		}
	}

	function getMessage($this) {
		let $sbMessage    = $this.find('.sb-message');
		let $sbButtonData = $this.find('.sb-button-data');

		let $activeBuilder = $sbButtonData.data('active-builder');
		let $elExist       = $sbButtonData.data('el-exist');
		let $wpbExist      = $sbButtonData.data('wpb-exist');

		if ('wpbakery' === $activeBuilder) {
			if (! $elExist) {
				$sbMessage.append('You need installed ELEMENTOR');
				return;
			}

			$sbMessage.append('ELEMENTOR activated');
		}else if ('elementor' === $activeBuilder) {
			if (! $wpbExist) {
				$sbMessage.append('You need installed WPBakery');
				return;
			}

			$sbMessage.append('WPBakery activated');
		}else if ( $elExist && ! $wpbExist ) {
			$sbMessage.append('ELEMENTOR activated');
		}else if ( $wpbExist && ! $elExist ) {
			$sbMessage.append('WPBakery activated');
		}else if ( $wpbExist && $elExist ) {
			$sbMessage.append('Activate the builder yourself');
		}else {
			$sbMessage.append('Please install builders');
		}

		getAnimation($sbMessage);
	}

	function getAnimation($obj){
		$obj.animate({
			top: '100%',
			opacity: "100%",
		}, 1000);
			
		setTimeout(function() {
			$obj.remove();
		}, 4000);
	}

	function switchBuilder($sbButton) {
		$sbButton.on( 'click', function( event ){
			event.preventDefault();

			let $this   = $(this);
			let $button = $this.find('a.ab-item');
	
			$button.addClass('sb-loading');
	
			$.ajax({
				url: ajaxurl,
				method: 'post',
				data: {
					action: 'switch-builder',
				},
				beforeSend: function() {
					$this.append('<p class="sb-message"></p>');
				},
				success: function() {
					getMessage($this);
					updateBuilderData($this);
				},
				error: function() {
					let $sbMessage = $this.find('.sb-message').append('<span class="sb-error">Bad Request !</span>');

					getAnimation($sbMessage);
				},
				complete: function() {
					$button.removeClass('sb-loading');
				}
			});
		});
	}

	$(document).ready(function() {
		switchBuilder($('.sb-button'));
	});
})(jQuery);
