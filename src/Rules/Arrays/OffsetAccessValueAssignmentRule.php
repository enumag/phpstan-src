<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<Expr>
 */
class OffsetAccessValueAssignmentRule implements \PHPStan\Rules\Rule
{

	/** @var RuleLevelHelper */
	private $ruleLevelHelper;

	public function __construct(RuleLevelHelper $ruleLevelHelper)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return Expr::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Assign
			&& !$node instanceof AssignOp
		) {
			return [];
		}

		if (!$node->var instanceof Expr\ArrayDimFetch) {
			return [];
		}

		$arrayDimFetch = $node->var;

		if ($node instanceof Assign) {
			$assignedValueType = $scope->getType($node->expr);
		} else {
			$assignedValueType = $scope->getType($node);
		}

		$originalArrayType = $scope->getType($arrayDimFetch->var);
		$arrayTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$arrayDimFetch->var,
			'',
			static function (Type $varType) use ($assignedValueType): bool {
				$result = $varType->setOffsetValueType(new MixedType(), $assignedValueType);
				return !($result instanceof ErrorType);
			}
		);
		$arrayType = $arrayTypeResult->getType();
		if ($arrayType instanceof ErrorType) {
			return [];
		}
		$isOffsetAccessible = $arrayType->isOffsetAccessible();
		if (!$isOffsetAccessible->yes()) {
			return [];
		}
		$resultType = $arrayType->setOffsetValueType(new MixedType(), $assignedValueType);
		if (!$resultType instanceof ErrorType) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'%s does not accept %s.',
				$originalArrayType->describe(VerbosityLevel::value()),
				$assignedValueType->describe(VerbosityLevel::typeOnly())
			))->build(),
		];
	}

}
