<?php
declare(strict_types = 1);
/**
 * /src/Controller/Profile/IndexController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\Profile;

use App\Entity\User;
use App\Security\RolesService;
use App\Utils\JSON;
use JsonException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class IndexController
 *
 * @package App\Controller\Profile
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class IndexController
{
    private SerializerInterface $serializer;
    private RolesService $rolesService;

    /**
     * ProfileController constructor.
     */
    public function __construct(SerializerInterface $serializer, RolesService $rolesService)
    {
        $this->serializer = $serializer;
        $this->rolesService = $rolesService;
    }

    /**
     * Endpoint action to get current user profile data.
     *
     * @Route(
     *     path="/profile",
     *     methods={"GET"}
     *  );
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
     * @SWG\Schema(
     *          ref=@Model(
     *              type=User::class,
     *              groups={"set.UserProfile"},
     *          ),
     *      ),
     *  )
     * @SWG\Response(
     *      response=401,
     *      description="Invalid token",
     * @SWG\Schema(
     *          type="object",
     * @SWG\Property(property="code", type="integer", description="Error code"),
     * @SWG\Property(property="message", type="string", description="Error description"),
     *      ),
     *      examples={
     *          "Token not found": "{code: 401, message: 'JWT Token not found'}",
     *          "Expired token": "{code: 401, message: 'Expired JWT Token'}",
     *      },
     *  )
     * @SWG\Tag(name="Profile")
     *
     * @throws JsonException
     */
    public function __invoke(User $loggedInUser): JsonResponse
    {
        /** @var array<string, string|array> $output */
        $output = JSON::decode(
            $this->serializer->serialize($loggedInUser, 'json', ['groups' => 'set.UserProfile']),
            true
        );

        /** @var array<int, string> $roles */
        $roles = $output['roles'];

        $output['roles'] = $this->rolesService->getInheritedRoles($roles);

        return new JsonResponse($output);
    }
}
