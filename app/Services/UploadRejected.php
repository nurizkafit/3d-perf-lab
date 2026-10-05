<?php

namespace App\Services;

use RuntimeException;

/** A file failed upload validation. The message is shown to the user next to the row. */
class UploadRejected extends RuntimeException {}
