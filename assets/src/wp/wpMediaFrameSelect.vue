<template>
	<div @click="openMediaModal" style="display: inline-block">
		<slot></slot>
	</div>
</template>

<script>
	export default {
		name: "wpMediaFrameSelect",
		props: {
			placeholderText: {type: String, default: 'No File Selected'},
			buttonText: {type: String, default: 'Add Image'},
			removeButtonText: {type: String, default: 'Remove'},
			modalTitle: {type: String, default: 'Select Image'},
			modalButtonText: {type: String, default: 'Set Image'},
			value: {type: Object},
		},
		methods: {
			openMediaModal() {
				let self = this;
				let frame = new wp.media.view.MediaFrame.Select({
					title: self.modalTitle,
					multiple: true,
					library: {
						order: 'DESC',
						orderby: 'date',
						type: 'image',
						search: null,
						uploadedTo: null
					},
					button: {text: self.modalButtonText}
				});
				frame.on('select', function () {
					let collection = frame.state().get('selection'), images = [];

					collection.each(function (attachment) {
						let sizes = attachment.attributes.sizes;

						images.push({
							id: attachment.id,
							title: attachment.attributes.title,
							src: sizes.full.url,
							thumbnail: sizes.thumbnail ? sizes.thumbnail.url : null,
							height: sizes.full.height,
							width: sizes.full.width,
						});
					});
					self.$emit('input', images);
				});
				frame.open();
			}
		}
	}
</script>

<style scoped>

</style>
