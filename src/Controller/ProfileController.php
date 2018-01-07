<?php
declare(strict_types = 1);
/**
 * /src/Controller/ProfileController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use App\Entity\User;
use App\Security\ApiKeyUser;
use App\Security\RolesService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ProfileController
 *
 * @Route(
 *      path="/profile",
 *  )
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ProfileController
{
    /**
     * Endpoint action to get current user profile data.
     *
     * @Route("");
     *
     * @Method("GET")
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
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="code", type="integer", description="Error code"),
     *          @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     *  )
     * @SWG\Tag(name="Profile")
     *
     * @param TokenStorageInterface $tokenStorage
     * @param SerializerInterface   $serializer
     * @param RolesService          $rolesService
     *
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function profileAction(
        TokenStorageInterface $tokenStorage,
        SerializerInterface $serializer,
        RolesService $rolesService
    ): JsonResponse {
        /** @var User|ApiKeyUser $user */
        /** @noinspection NullPointerExceptionInspection */
        $user = $tokenStorage->getToken()->getUser();

        // Get serializer groups for current user instance
        $groups = $this->getSerializationGroupsForProfile($rolesService, $user);

        // Create response
        return new JsonResponse($serializer->serialize($user, 'json', ['groups' => $groups]), 200, [], true);
    }

    /**
     * Endpoint action to get current user roles as an array.
     *
     * @Route("/roles");
     *
     * @Method("GET")
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
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="code", type="integer", description="Error code"),
     *          @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     *  )
     * @SWG\Tag(name="Profile")
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

    /**
     * Endpoint action to get current user user groups.
     *
     * @Route("/groups");
     *
     * @Method("GET")
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
     *      description="User groups",
     *      @SWG\Schema(
     *          type="array",
     *          @Model(
     *              type=App\Entity\UserGroup::class,
     *              groups={"UserGroup", "UserGroup.role"},
     *          ),
     *      ),
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Invalid token",
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="code", type="integer", description="Error code"),
     *          @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     *  )
     * @SWG\Response(
     *      response=403,
     *      description="Access denied",
     *      @SWG\Schema(
     *          type="403",
     *          @SWG\Property(property="code", type="integer", description="Error code"),
     *          @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *      examples={
     *          "Access denied": "{code: 403, message: 'Access denied'}",
     *      },
     *  )
     * @SWG\Tag(name="Profile")
     *
     * @param TokenStorageInterface $tokenStorage
     * @param SerializerInterface   $serializer
     *
     * @return JsonResponse
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function groupsAction(TokenStorageInterface $tokenStorage, SerializerInterface $serializer): ?JsonResponse
    {
        /** @var User|ApiKeyUser $user */
        /** @noinspection NullPointerExceptionInspection */
        $user = $tokenStorage->getToken()->getUser();

        $data = null;

        if ($user instanceof User) {
            $data = $user->getUserGroups();
        } elseif ($user instanceof ApiKeyUser) {
            $data = $user->getApiKey()->getUserGroups();
        }

        if ($data === null) {
            throw new AccessDeniedException('Not supported user');
        }

        $groups = [
            'groups' => $this->getUserGroupGroups(),
        ];

        return new JsonResponse($serializer->serialize($data, 'json', $groups), 200, [], true);
    }

    /**
     * @param RolesService  $rolesService
     * @param UserInterface $user
     *
     * @return array|null
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    private function getSerializationGroupsForProfile(RolesService $rolesService, UserInterface $user): ?array
    {
        if ($user instanceof User) {
            $groups = $this->getSerializationGroupsForUser();

            // Set roles service to user entity, so we can get inherited roles
            $user->setRolesService($rolesService);
        } elseif ($user instanceof ApiKeyUser) {
            $groups = $this->getSerializationGroupsForApiKey();
        }

        if (!isset($groups)) {
            throw new AccessDeniedException('Not supported user');
        }

        return $groups;
    }

    /**
     * @return array
     */
    private function getSerializationGroupsForUser(): array
    {
        return \array_merge(
            [
                'User',
                'User.userGroups',
                'User.roles',
            ],
            $this->getUserGroupGroups()
        );
    }

    /**
     * @return array
     */
    private function getSerializationGroupsForApiKey(): array
    {
        return array_merge(
            [
                'ApiKeyUser',
                'ApiKeyUser.apiKey',
                'ApiKey.description',
                'ApiKey.userGroups',
            ],
            $this->getUserGroupGroups()
        );
    }

    /**
     * @return array
     */
    private function getUserGroupGroups(): array
    {
        return [
            'UserGroup',
            'UserGroup.role',
        ];
    }
}
