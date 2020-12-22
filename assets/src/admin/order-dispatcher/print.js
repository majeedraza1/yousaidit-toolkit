const printPage = {
	closePrint() {
		document.body.removeChild(this.__container__);
	},

	setPrint() {
		this.contentWindow.__container__ = this;
		this.contentWindow.onbeforeunload = this.closePrint;
		this.contentWindow.onafterprint = this.closePrint;
		this.contentWindow.focus(); // Required for IE
		this.contentWindow.print();
	},

	printPage(sURL) {
		let hiddenFrame = document.createElement("iframe");
		hiddenFrame.onload = this.setPrint;
		hiddenFrame.style.position = "fixed";
		hiddenFrame.style.right = "0";
		hiddenFrame.style.bottom = "0";
		hiddenFrame.style.width = "0";
		hiddenFrame.style.height = "0";
		hiddenFrame.style.border = "0";
		hiddenFrame.src = sURL;
		document.body.appendChild(hiddenFrame);
	}
}

export {printPage}
export default printPage;
