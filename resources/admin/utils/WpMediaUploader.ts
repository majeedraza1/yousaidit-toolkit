const wpMediaUploader = () => {
  return new Promise(resolve => {
    let frame = new window.wp.media.view.MediaFrame.Select({
      title: 'Add PDF',
      multiple: false,
      library: {
        order: 'ASC',
        orderby: 'title',
        type: 'application/pdf',
        search: null,
        uploadedTo: null
      },

      button: {text: 'Use PDF'}
    });

    frame.on('select', function () {
      let collection = frame.state().get('selection'), ids = 0;

      collection.each(function (attachment: { id: number }) {
        ids = attachment.id;
      });

      resolve(ids);
    });

    frame.open();
  })
}

export default wpMediaUploader;
