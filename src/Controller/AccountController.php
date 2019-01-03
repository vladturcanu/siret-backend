<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\JsonRequestService;
use App\Service\TokenGenerator;

class AccountController extends AbstractController
{
    /**
     * @Route("/signup", name="signup")
     * @Route("/signup/", name="signup2")
     * 
     * Params: username, name, surname, password, email, type, city
     */
    public function signup(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $username = $jsr->getArrayKey('username', $parameters);
        $name = $jsr->getArrayKey('name', $parameters);
        $surname = $jsr->getArrayKey('surname', $parameters);
        $email = $jsr->getArrayKey('email', $parameters);
        $plain_pw = $jsr->getArrayKey('password', $parameters);
        $type = $jsr->getArrayKey('type', $parameters);
        $city = $jsr->getArrayKey('city', $parameters);

        /* Test username, email, password, type not empty */
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }
        if (!$plain_pw) {
            return $this->json([
                'error' => 'No password supplied'
            ]);
        }
        if (!$name) {
            return $this->json([
                'error' => 'No name supplied'
            ]);
        }
        if (!$surname) {
            return $this->json([
                'error' => 'No surname supplied'
            ]);
        }
        if (!$email) {
            return $this->json([
                'error' => 'No email supplied'
            ]);
        }
        if (!$type) {
            return $this->json([
                'error' => 'No type supplied'
            ]);
        }

        /* Test if the user exists */
        $user_repo = $this->getDoctrine()->getRepository(Account::class);

        /* Test unique username */
        $existing_user = $user_repo->findOneBy(['username' => $username]);
        if ($existing_user) {
            return $this->json([
                'error' => 'Username already exists'
            ]);
        }

        /* Test unique email */
        $existing_user = $user_repo->findOneBy(['email' => $email]);
        if ($existing_user) {
            return $this->json([
                'error' => 'Email already exists'
            ]);
        }

        /* Create user with supplied data */
        $user = new Account();
        $password = password_hash($plain_pw, PASSWORD_BCRYPT);
        if (!ctype_alnum($username)) {
            return $this->json([
                'error' => 'Invalid username: only alphanumeric characters allowed'
            ]);
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE) {
            return $this->json([
                'error' => 'Invalid email'
            ]);
        }

        $user->setUsername($username);
        $user->setPassword($password);
        $user->setEmail($email);
        $user->setType($type);
        $user->setName($name);
        $user->setSurname($surname);
        $user->setCity($city);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'User added successfully!'
        ]);
    }

    /**
     * @Route("/login", name="login")
     * @Route("/login/", name="login2")
     * 
     * Params: username, password
     */
    public function login(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $username = $jsr->getArrayKey('username', $parameters);
        $password = $jsr->getArrayKey('password', $parameters);

        /* Test username, password not empty */
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }
        if (!$password) {
            return $this->json([
                'error' => 'No password supplied'
            ]);
        }

        /* Find username + password combination in database */
        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $user = $user_repo->findOneBy([
            'username' => $username
        ]);

        if ($user) {
            /* Test matching password */
            if (!password_verify($password, $user->getPassword())) {
                return $this->json([
                    'error' => 'Wrong username or password. Try again!'
                ]);
            }

            /* Generate token for the user */
            $token = TokenGenerator::generateRandomString();

            /* Insert token into the database */
            $user->setToken($token);
            $em->persist($user);
            $em->flush();

            return $this->json([
                'username' => $username,
                'token' => $token,
                'type' => $user->getType(),
                'is_valid' => $user->getIsValid()
            ]);
        } else {
            return $this->json([
                'error' => 'Wrong username or password. Try again!'
            ]);
        }
    }

    /**
     * @Route("/login_form", name="login_form")
     * @Route("/login_form/", name="login_form2")
     * 
     * Receives params from a form instead of JSON.
     * Params: username, password
     */
    public function login_form(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();

        $username = $request->request->get("username");
        $password = $request->request->get("password");

        /* Test username, password not empty */
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }
        if (!$password) {
            return $this->json([
                'error' => 'No password supplied'
            ]);
        }

        /* Find username + password combination in database */
        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $user = $user_repo->findOneBy([
            'username' => $username
        ]);

        if ($user) {
            /* Test matching password */
            if (!password_verify($password, $user->getPassword())) {
                return $this->json([
                    'error' => 'Wrong username or password. Try again!'
                ]);
            }

            /* Generate token for the user */
            $token = TokenGenerator::generateRandomString();

            /* Insert token into the database */
            $user->setToken($token);
            $em->persist($user);
            $em->flush();

            return $this->json([
                'username' => $username,
                'token' => $token,
                'type' => $user->getType(),
                'is_valid' => $user->getIsValid()
            ]);
        } else {
            return $this->json([
                'error' => 'Wrong username or password. Try again!'
            ]);
        }
    }

    /**
     * @Route("/logout_all", name="logout_all")
     * @Route("/logout_all/", name="logout_all2")
     * 
     * Params: token
     */
    public function logout_all(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }

        /* Find username in database by token */
        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $user = $user_repo->findOneBy([
            'token' => $token
        ]);

        if ($user) {
            /* Remove user token from database */
            $user->setToken("");
            $em->persist($user);
            $em->flush();

            return $this->json([
                'message' => 'Logout successful!'
            ]);
        } else {
            return $this->json([
                'error' => 'Error while logging out'
            ]);
        }
    }


    /**
     * @Route("/get_user_data", name="get_user_data")
     * @Route("/get_user_data/", name="get_user_data2")
     * 
     * Params: token, username
     */
    public function get_user_data()
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);
        $username = $jsr->getArrayKey('username', $parameters);

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }

        /* Find the logged user in database by token, to make sure the token is real (he's authorized) */
        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $logged_user = $user_repo->findOneBy([
            'token' => $token
        ]);

        if (!$logged_user) {
            return $this->json([
                'error' => 'Invalid login. Must be logged in to view user details.'
            ]);
        }

        /* Get the requested username's data from the database */
        $user = $user_repo->findOneBy([
            'username' => $username
        ]);

        if (!$user) {
            return $this->json([
                'error' => 'Requested user not found in the database.'
            ]);
        }
        
        return $this->json([
            'username' => $user->getUsername(),
            'type' => $user->getType(),
            'name' => $user->getName(),
            'surname' => $user->getSurname(),
            'city' => $user->getCity(),
            'email' => $user->getEmail(),
            'is_valid' => $user->getIsValid()
        ]);
    }


    /**
     * @Route("/get_users", name="get_users")
     * @Route("/get_users/", name="get_users2")
     * 
     * Params: token (must be admin)
     */
    public function get_users()
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }

        /* Find the logged user in database by token, to make sure the token is real (he's authorized) */
        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $logged_user = $user_repo->findOneBy([
            'token' => $token
        ]);

        if (!$logged_user || $logged_user->getType() != 'admin') {
            return $this->json([
                'error' => 'Only administrators are allowed to view the list of users.'
            ]);
        }

        $user_list = $user_repo->findAll();

        $users = [];
        foreach ($user_list as $user) {
            array_push($users, [
                'username' => $user->getUsername(),
                'type' => $user->getType(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'city' => $user->getCity(),
                'email' => $user->getEmail(),
                'is_valid' => $user->getIsValid()
            ]);
        }

        return $this->json($users);
    }

    /**
     * @Route("/validate_user", name="validate_user")
     * @Route("/validate_user/", name="validate_user2")
     * 
     * Params: token, username (must be admin)
     */
    public function validate_user(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);
        $username = $jsr->getArrayKey('username', $parameters);

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }

        /* Find logged user in database by token, to make sure the token is real (he's authorized) */
        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $user = $user_repo->findOneBy([
            'token' => $token
        ]);

        if (!$user || $user->getType() != "admin") {
            return $this->json([
                'error' => 'Only administrators are authorized to view users.'
            ]);
        }
        
        $validated_user = $user_repo->findOneBy([
            'username' => $username
        ]);

        if (!$validated_user) {
            return $this->json([
                'error' => 'User not found in database.'
            ]);
        }

        if ($validated_user->getIsValid()) {
            return $this->json([
                'error' => 'User has already been validated.'
            ]);
        }

        $validated_user->setIsValid(TRUE);
        $em->persist($validated_user);
        $em->flush();

        return $this->json([
            "message" => "User validated successfully"
        ]);
    }

    /**
     * @Route("/invalidate_user", name="invalidate_user")
     * @Route("/invalidate_user/", name="invalidate_user2")
     * 
     * Params: token, username (must be admin)
     */
    public function invalidate_user(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);
        $username = $jsr->getArrayKey('username', $parameters);

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }

        /* Find logged user in database by token, to make sure the token is real (he's authorized) */
        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $user = $user_repo->findOneBy([
            'token' => $token
        ]);

        if (!$user || $user->getType() != "admin") {
            return $this->json([
                'error' => 'Only administrators are authorized to view users.'
            ]);
        }
        
        $validated_user = $user_repo->findOneBy([
            'username' => $username
        ]);

        if (!$validated_user) {
            return $this->json([
                'error' => 'User not found in database.'
            ]);
        }

        if (!$validated_user->getIsValid()) {
            return $this->json([
                'error' => 'User has already been validated.'
            ]);
        }

        $validated_user->setIsValid(FALSE);
        $em->persist($validated_user);
        $em->flush();

        return $this->json([
            "message" => "User invalidated successfully"
        ]);
    }


    /**
     * @Route("/delete_user", name="delete_user")
     * @Route("/delete_user/", name="delete_user2")
     * 
     * Params: token (can only delete your own account)
     */
    public function delete_user(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }

        /* Find the logged user in database by token, to make sure the token is real (he's authorized) */
        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $logged_user = $user_repo->findOneBy([
            'token' => $token
        ]);

        if (!$logged_user) {
            return $this->json([
                'error' => 'In order to delete your account, you must be logged in.'
            ]);
        }

        /* Delete the account from the database */
        $em->remove($logged_user);
        $em->flush();

        return $this->json([
            'message' => 'Your account was deleted successfully!'
        ]);
    }


    /**
     * @Route("/edit_user_data", name="edit_user_data")
     * @Route("/edit_user_data/", name="edit_user_data/")
     * 
     * Params: token, name, surname, email, city
     */
    public function edit_user_data(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);
        $name = $jsr->getArrayKey('name', $parameters);
        $surname = $jsr->getArrayKey('surname', $parameters);
        $email = $jsr->getArrayKey('email', $parameters);
        $city = $jsr->getArrayKey('city', $parameters);

        /* Test username, email, password, type not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }
        if (!$name) {
            return $this->json([
                'error' => 'No name supplied'
            ]);
        }
        if (!$surname) {
            return $this->json([
                'error' => 'No surname supplied'
            ]);
        }
        if (!$email) {
            return $this->json([
                'error' => 'No email supplied'
            ]);
        }

        /* Test if the user exists */
        $user_repo = $this->getDoctrine()->getRepository(Account::class);

        /* Get user by the supplied token */
        $user = $user_repo->findOneBy(['token' => $token]);
        if (!$user) {
            return $this->json([
                'error' => 'Please log in in order to change your account details.'
            ]);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE) {
            return $this->json([
                'error' => 'Invalid email'
            ]);
        }

        $user->setEmail($email);
        $user->setName($name);
        $user->setSurname($surname);

        if ($city) {
            $user->setCity($city);
        }

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Account details edited successfully!'
        ]);
    }
}
