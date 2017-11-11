<?php
declare(strict_types = 1);
/**
 * /src/Controller/AuthController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use App\Entity\User;
use App\Security\RolesService;
use App\Utils\JSON;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class AuthController
 *
 * @Route(
 *      path="/auth",
 *  )
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthController
{
    /**
     * Endpoint action to get user Json Web Token (JWT) for authentication.
     *
     * @Route("/getToken");
     *
     * @Method("POST")
     *
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      description="Credentials object",
     *      required=true,
     *      @SWG\Schema(
     *          example={"username": "username", "password": "password"}
     *      )
     *  )
     * @SWG\Response(
     *      response=200,
     *      description="JSON Web Token for user",
     *  )
     * @SWG\Response(
     *      response=400,
     *      description="Invalid body content",
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Bad credentials",
     *  )
     * @SWG\Tag(name="Authentication")
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function getTokenAction(): void
    {
        $message = \sprintf(
            'You need to send JSON body to obtain token eg. %s',
            JSON::encode(['username' => 'username', 'password' => 'password'])
        );

        throw new HttpException(400, $message);
    }

    /**
     * Endpoint action to get current user profile data.
     *
     * @Route("/profile");
     *
     * @Method("GET")
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     *
     * @SWG\Parameter(
     *      type="string",
     *      name="Authorization",
     *      in="header",
     *      required=true,
     *      description="Authorization header",
     *      default="Bearer _your_jwt_here_",
     *  )
     * @SWG\Response(
     *      response=200,
     *      description="User profile data",
     *      @SWG\Schema(
     *          @Model(
     *              type=User::class,
     *              groups={"User", "User.userGroups", "User.roles", "UserGroup", "UserGroup.role"},
     *          ),
     *      ),
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Invalid token",
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     *  )
     * @SWG\Tag(name="Authentication")
     *
     * @param TokenStorageInterface $tokenStorage
     * @param SerializerInterface   $serializer
     * @param RolesService          $rolesService
     *
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function profileAction(
        TokenStorageInterface $tokenStorage,
        SerializerInterface $serializer,
        RolesService $rolesService
    ): JsonResponse {
        /** @var User $user */
        /** @noinspection NullPointerExceptionInspection */
        $user = $tokenStorage->getToken()->getUser();

        // Set roles service to user entity, so we can get inherited roles
        $user->setRolesService($rolesService);

        // Create response
        return new JsonResponse(
            $serializer->serialize(
                $user,
                'json',
                [
                    'groups' => [
                        'User',
                        'User.userGroups',
                        'User.roles',
                        'UserGroup',
                        'UserGroup.role',
                    ]
                ]
            ),
            200,
            [],
            true
        );
    }

    /**
     * Endpoint action to get current user roles as an array.
     *
     * @Route("/roles");
     *
     * @Method("GET")
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     *
     * @SWG\Parameter(
     *      type="string",
     *      name="Authorization",
     *      in="header",
     *      required=true,
     *      description="Authorization header",
     *      default="Bearer _your_jwt_here_",
     *  )
     * @SWG\Response(
     *      response=200,
     *      description="User roles",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(type="string"),
     *      ),
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Invalid token",
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     *  )
     * @SWG\Tag(name="Authentication")
     *
     * @param TokenStorageInterface $tokenStorage
     * @param RolesService          $rolesService
     *
     * @return JsonResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function rolesAction(TokenStorageInterface $tokenStorage, RolesService $rolesService): JsonResponse
    {
        /** @noinspection NullPointerExceptionInspection */
        $user = $tokenStorage->getToken()->getUser();

        return new JsonResponse($rolesService->getInheritedRoles($user->getRoles()));
    }
}
