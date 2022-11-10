# Todo

## Video Inner Massage

- [ ] Add another slider before inner message to upload video or to add a message
	- [x] Video Message
		- [x] Show video uploader button
		- [x] Re-name video to a unique and unpredictable 64 character length name.
		- [x] If video is uploaded, then show the video
		- [x] Add option to clear the video
		- [x] On submit, add the message to cart item data
		- [x] Re-calculate item price based on the video or message additional price
		- [x] On order complete, add the video to order item data
		- [x] Remove video from background task to delete if customer is not logged in
	- [ ] Text Message
		- [x] Show inner message editor
		- [ ] If message is added, then show the message
		- [x] Add option to clear the message
		- [x] On submit, add the message to cart item data
		- [x] Re-calculate item price based on the message additional price
		- [x] On order complete, add the message to order item data
- [ ] Add custom table to store the video details
	- [ ] Video ID, Customer ID (Null if not logged in), Order ID (Null if not ordered yet), Uploaded Date, Deleted Date
- [ ] Add background task to remove the video from the server after 24 hours if customer is not logged in
- [ ] Add background task to delete the video after 6 months from upload date or last order date
	- [ ] Add option to use same video for another order
- [ ] Add QR code on card inside that will take user to view video
- [ ] Add menu on my-account page to manage the videos by the customer
	- [ ] Give option to delete the video
	- [ ] Show info when it will be deleted from server
	- [ ] Show relation with order if any
