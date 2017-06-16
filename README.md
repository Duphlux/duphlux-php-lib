# Duphlux API PHP Library

- [Installation](https://github.com/Duphlux/duphlux-php-lib#installation)
- [Usage](https://github.com/Duphlux/duphlux-php-lib#usage)


## Installation

The preferred way to install this extension is through composer.

Either run

```bash
composer require Duphlux/duphlux-php-lib
```

**or**

add '"Duphlux/duphlux-php-lib": "1.0.0"' to the require section of your composer.json file, then run:

```bash
composer install
```

## Usage

First of all, create an account on [Duphlux](www.duphlux.com) - You will need your account token to use the service.

There are two operations that can be performed on the system:

+ **authenticate:** Initialize a phone number verification request

  ```php

    use duphlux/Duphlux;

    //duphlux account token
    $token = "45111f5f33d1701752fa6ebae644dff32403880c6";

    // Instantiate the duphlux class, requires your account token
    $duphlux = new Duphlux($token);

    //you can generate reference using the generateRef method (note that uniqueness is not guaranteed)
    //you may choose to generate your reference another way
    $reference = $duphlux->generateRef(32);

    //set up the request parameters for the authentication request
    $options = ['phone_number'=>'08079189198','transaction_reference'=>$reference,
              'timeout'=>60,'redirect_url'=>'http://www.mysite.com/duphlux-redirect'];

    // Initializing an authentication request
    //you can chain this method with the redirect method to immediately redirect to the duphlux verification url
    $duphlux->authenticate($options);

  ```

  **or**

  ```php

    //chained authenticate request with redirect method
    $duphlux->authenticate($options)->redirect();

  ```

  To check if the api call was successfull

  ```php

    //checks if an error occurred during the operation
    if ($duphlux->hasError)
    {
        //gets the request error
        $error = $duphlux->getError();
    }
    else
    {
        //gets the request response returned from the request
        //you may specify a specific key to get the value from the response information
        $response = $duphlux->getResponse();

        //
    }
  ```

+ **checkStatus:** Check the status of a previous request

  ```php

    use duphlux/Duphlux;

    //duphlux account token
    $token = "45111f5f33d1701752fa6ebae644dff32403880c6";

    // Instantiate the duphlux class
    $duphlux = new Duphlux($token);

    //previous authentication request reference for which you want to inquire about the status
    $reference = getReferenceFromMyDb();

    // check the status of an authentication request
    // the previous authentication request reference is required, it is passed as a parameter
    $status = $duphlux->checkStatus($reference);

  ```

    you can chain the above method with the following:

    ```php

    //checks the response gotten to see whether the phone number verification was successfull
    $verified = $status->isVerified();

    //checks the response gotten to see whether the phone number verification is still pending
    $pending = $status->isPending();

    //checks the response gotten to see whether the phone number verification failed
    $failed = $status->isFailed();

  ```

  Also, you can set the following properties - beforeSend and afterSend, which must be callbacks,
  before performing any operation. They are passed an instance of the Duphlux class and are
  called before and after an api request is made. See example below:

  ```php

    use duphlux/Duphlux;

    //duphlux account token
    $token = "45111f5f33d1701752fa6ebae644dff32403880c6";

    //previous authentication request reference for which we want to inquire about the status
    $reference = getReferenceFromMyDb();

    //this sets the beforeSend handler which is triggered before a any request is made
    //this can be any callable (anonymous function or class method)
    $duphlux->beforeSend = function($duphlux)
    {
        //here you can now perform any logic u want
        log($duphlux->getRequestOptions());
    };

    //this sets the afterSend handler which is triggered after a request is made
    //this can be any callable (anonymous function or class method)
    $duphlux->afterSend = function($duphlux)
    {
        //here you can now perform any logic u want
        log($duphlux->getResponse());
    };

    // Instantiate the duphlux class
    $duphlux = new Duphlux($token);

    // verify the status of an authentication request
    // the previous authentication request reference is required, it is passed as a parameter
    $duphlux->checkStatus($reference);

  ```