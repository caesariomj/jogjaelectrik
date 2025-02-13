export default (
    data = {
        model: '',
        isThumbnail: false,
    },
) => ({
    thumbnail: null,
    images: [],

    isDropping: false,
    isUploading: false,
    isDeleting: false,
    progress: 0,
    model: data.model,
    isThumbnail: data.isThumbnail,

    handleFileSelect(event) {
        const $this = this;

        if ($this.isThumbnail) {
            if (event.target.files.length > 0) {
                $this.thumbnail = event.target.files[0];

                $this.uploadFiles(event.target.files[0]);
            }
        } else {
            if (event.target.files.length) {
                Array.from(event.target.files).forEach((file) => {
                    $this.images.push(file);
                });

                $this.uploadFiles(event.target.files);
            }
        }
    },

    handleFileDrop(event) {
        const $this = this;

        if (event.dataTransfer.files.length > 0) {
            if ($this.isThumbnail) {
                $this.thumbnail = event.dataTransfer.files[0];

                $this.uploadFiles(event.dataTransfer.files[0]);
            } else {
                Array.from(event.dataTransfer.files).forEach((file) => {
                    $this.images.push(file);
                });

                $this.uploadFiles(event.dataTransfer.files);
            }
        }
    },

    uploadFiles(files) {
        const $this = this;

        $this.isUploading = true;

        if ($this.isThumbnail) {
            $this.$wire.upload(
                $this.model,
                files,
                function (success) {
                    $this.thumbnail = null;
                    $this.isUploading = false;
                    $this.progress = 0;
                },
                function (error) {
                    $this.thumbnail = null;
                    $this.isUploading = false;
                    $this.progress = 0;
                    console.log('error', error);
                },
                function (event) {
                    $this.progress = event.detail.progress;
                },
            );
        } else {
            $this.$wire.$uploadMultiple(
                $this.model,
                files,
                function (success) {
                    $this.images = [];
                    $this.isUploading = false;
                    $this.progress = 0;
                },
                function (error) {
                    $this.images = [];
                    $this.isUploading = false;
                    $this.progress = 0;
                    console.log('error', error);
                },
                function (event) {
                    $this.progress = event.detail.progress;
                },
            );
        }
    },

    removeUpload(filename) {
        const $this = this;

        $this.isDeleting = true;

        $this.$wire.$removeUpload($this.model, filename, function (success) {
            $this.isDeleting = false;
        });
    },

    formatFileSize(bytes) {
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];

        if (bytes === 0) return '0 Bytes';
        const i = Math.floor(Math.log(bytes) / Math.log(1024));

        return `${(bytes / Math.pow(1024, i)).toFixed(2)} ${sizes[i]}`;
    },
});
