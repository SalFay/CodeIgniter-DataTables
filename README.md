Data Tables Library for CodeIgniter v1.0 - Alpha
-

* This Library makes it very easy to Integrate Ajax based DataTables in CodeIgniter
* It uses CodeIgniter's Database Query Builder API so you have all the powers in one place
* It does not require any interface Class or extra Model to construct the query
* It enables you to bind custom search, Where Clauses, Having Clauses, Group by and Order by clauses.
* It can also mutate (Edit) Query field before sendng it to the DataTables.
* It makes it easy to Add Action (Edit/Delete/View etc) Columns to the Query and you can easily bind it to the Table.
* It can format Columns as Currency, Percent, Date and Number and You can use its Edit COlumn ability to have any other formating and calculation.

Sample Usage
-
To understand how to use this library and get full benefits of it, Follow the step by step process below

#### Download the Library
* Download the library by clicking here or cloning it via git or whatever method you like.
* You will get a zip file (If you downloaded from the link above).
* Extract the Zip library so you can get the package files
* Locate `application/libraries/DataTables.php` file.

#### Installing Library
* Copy/Move the `DataTables.php` file from the package `application/libraries` directory
* And paste it into your CodeIgniter's `application/libaries` directory.
* Open `application/config/autoload.php` from your Codeigniter's Directory.
* Locate `$autoload['libraries'] = array(...)` and add `DataTables` to the array.
* Save changes to the `autoload.php` file.

#### Sample Data
* We are going to use [Employees Sample Database](https://dev.mysql.com/doc/employee/en/)
* Download this database from [here](https://github.com/datacharmer/test_db)
* Database Schema can be located [here](https://dev.mysql.com/doc/employee/en/sakila-structure.html)
* Follow the instruction in the Database Github page and Intsall this database so we can use it smoothly in our examples.

#### Create Controller
* Go ahead and create a Controller class with the name `Company.php` in `application/controllers/` directory
* Paste the following code into the `Company.php`

```
class Company extends CI_Controller{
    public function employees_ajax(){
        echo '{}';
    }
    	
    public function employees(){
        $this->load->view('employees');
    }
    	
    public function departments_ajax(){
        echo '{}';
    }
        	
    public function departments(){
        $this->load->view('departments');
    }
}
```

#### Create View
* You must download or generate a DataTables Js/Css Library from the DataTables.net website
* You can use thi sview to get started, We have generated a full package compatible with our example.
* Goto `application/views/` and create a file with name `employees.php`
* Paste the following code into the `employees.php` file
```
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employees</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <h1>Hello, world!</h1>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  </body>
</html>
```
