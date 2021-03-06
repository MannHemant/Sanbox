<?php
require_once(LIB_PATH.DS.'database.php');

class Product extends DatabaseObject {
	protected static $table_name = "products";
	protected static $db_fields = ['id', 'user_id', 'category', 'name', 'price', 'pur_year',
		'description', 'filename', 'file_size'];
	public $id;
	public $user_id;
	public $category;
	public $name;
	public $price;
	public $pur_year;
	public $description;
	public $filename;
	public $file_size;
	
	private $temp_path;
	protected $upload_dir = "images";
	public $errors = array();

	protected $upload_errors = [
		UPLOAD_ERR_OK => "No errors.",
		UPLOAD_ERR_INI_SIZE => "Larger than upload_max_filesize.",
		UPLOAD_ERR_FORM_SIZE => "Larger than form MAX_FILE_SIZE",
		UPLOAD_ERR_PARTIAL => "Partial upload",
		UPLOAD_ERR_NO_FILE => "No file",
		UPLOAD_ERR_NO_TMP_DIR => "No temporary directory.",
		UPLOAD_ERR_CANT_WRITE => "Can't write to disk",
		UPLOAD_ERR_EXTENSION => "File upload stoped by extension."
	];

	// Pass in $_FILE(['uploaded_file']) as an argument
	public function attach_file($file) {
		// Perform error checking on the form parameters
		if(!$file || empty($file) || !is_array($file)) {
			// error: nothing uploaded or wrong argument usage
			$this->errors[] = "No file was uploaded";
			return false;
		} elseif ($file['error']!=0) {
			// error: report what PHP says went wrong
			$this->errors[] = $this->upload_errors[$file['error']];
			return false;
		} else {
			// Set object attributes to the form parameter
			$this->temp_path = $file['tmp_name'];
			$this->filename = basename($file['name']);
			$this->file_size = $file['size'];
			return true;
		}
	}

	public function save() {
		// A new record won't have an id yet
		if(isset($this->id)) {
			// To update the description or price
			$this->update();
		} else {
			// Make sure there are no errors

			//Can't save if there are pre-existing errors
			if(!empty($this->errors)) { return false; }

			// Make sure the category is not too long for the DB
			if(strlen($this->category) > 30) {
				$this->errors[] = "The category can only be 30 characters long";
				return false;
			} elseif(empty($this->category)) {
				$this->errors[] = "Please provide a category";
				return false;
			}

			// Make sure a name is give
			if(empty($this->name)) {
				$this->errors[] = "Please provide the product name reflecting its model";
				return false;
			} elseif(strlen($this->name) > 50) {
				$this->errors[] = "Name can only be 50 characters long";
				return false;
			}

			// Make sure the price is given
			if(empty($this->price)) {
				$this->errors[] = "Please give a price";
				return false;
			}

			// Make sure the product has a description
			if(empty($this->description)) {
				$this->errors[] = "Please give atleast a small description";
				return false;
			}

			// Can't save without filename and temp location
			if(empty($this->filename) || empty($this->temp_path)) {
				$this->errors[] = "The file location was not available.";
				return false;
			}

			//Determine the target_path
			$target_path = SITE_ROOT .DS.'public' .DS. $this->upload_dir .DS.
				$this->filename;

			// Make sure a file doesn't already exist in the target location
			if(file_exists($target_path)) {
				$this->errors[] = "The file {$this->filename} already exits!";
				return false;
			}

			// Attempt to move the file
			if (move_uploaded_file($this->temp_path, $target_path)) {
				// Success
				// Save a corresponding entry to the database
				if($this->create()) {
					unset($this->temp_path);
					return true;
				}
			} else {
				// Failure
				$this->errors[] = "The file upload failed, internal error.";
				return false; 
			}	
		}
	}  

	public function destroy() {
		// First remove the databse entry
		if($this->delete()) {
			// then remove the file
			// Note that even though the database entry is gone, this object
			// is still around (which lets us use $this->image_path())
			$target_path = SITE_ROOT.DS.'public'.DS.$this->image_path();
			return unlink($target_path) ? true: false;
		} else {
			// database deletion failed.
			return false;
		}
		// then remove the file
	}

	public function image_path() {
		return $this->upload_dir."/".$this->filename;
	}


}

?>