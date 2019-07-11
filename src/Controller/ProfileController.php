<?php
declare(strict_types = 1);
/**
 * /src/Controller/ProfileController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Controller;

use App\Entity\User;
use App\Security\RolesService;
use App\Utils\JSON;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use function array_merge;

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
     * @Route(
     *     path="",
     *     methods={"GET"}
     *  );
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
     *          ref=@Model(
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
     * @param SerializerInterface   $serializer
     * @param RolesService          $rolesService
     * @param User                  $user
     *
     * @return JsonResponse
     */
    public function profileAction(
        SerializerInterface $serializer,
        RolesService $rolesService,
        User $user
    ): JsonResponse {
        // Get serializer groups for current user instance
        $groups = $this->getSerializationGroupsForUser();

        /** @var array<string, string|array> $output */
        $output = JSON::decode($serializer->serialize($user, 'json', ['groups' => $groups]), true);

        /** @var array<int, string> $roles */
        $roles = $output['roles'];

        $output['roles'] = $rolesService->getInheritedRoles($roles);

        return new JsonResponse($output);
    }

    /**
     * Endpoint action to get current user roles as an array.
     *
     * @Route(
     *     path="/roles",
     *     methods={"GET"},
     *  );
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
     * @param RolesService $rolesService
     * @param User         $user
     *
     * @return JsonResponse
     */
    public function rolesAction(RolesService $rolesService, User $user): JsonResponse
    {
        return new JsonResponse($rolesService->getInheritedRoles($user->getRoles()));
    }

    /**
     * Endpoint action to get current user user groups.
     *
     * @Route(
     *     path="/groups",
     *     methods={"GET"}
     *  );
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
     *          @SWG\Items(
     *              ref=@Model(
     *                  type=App\Entity\UserGroup::class,
     *                  groups={"UserGroup", "UserGroup.role"},
     *              ),
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
     * @param SerializerInterface $serializer
     * @param User                $user
     *
     * @return JsonResponse
     */
    public function groupsAction(SerializerInterface $serializer, User $user): JsonResponse
    {
        $groups = [
            'groups' => $this->getUserGroupGroups(),
        ];

        return new JsonResponse($serializer->serialize($user->getUserGroups(), 'json', $groups), 200, [], true);
    }

    /**
     * @return string[]
     */
    private function getSerializationGroupsForUser(): array
    {
        return array_merge(
            [
                'User',
                'User.userGroups',
                'User.roles',
            ],
            $this->getUserGroupGroups()
        );
    }

    /**
     * @return string[]
     */
    private function getUserGroupGroups(): array
    {
        return [
            'UserGroup',
            'UserGroup.role',
        ];
    }
}
