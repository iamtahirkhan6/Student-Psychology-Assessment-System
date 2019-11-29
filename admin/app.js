/**
 * Custom AngularJS App File
 * for populating and creating
 * form fields dynamically
 **/

var app = angular.module("Psychology-Test-Build-Form", []);

app.controller("build_questionnaire", function($scope) {

  $scope.questionnaire = [];
  $scope.questions = [];
  $scope.scoring_keys = [];

  ///////////////////////////////////////////////////////// STEP 1

  $scope.step_1_visible = true;
  $scope.step_2_visible = false;
  $scope.step_3_visible = false;

  $scope.step_1 = function() {
    console.log($scope.name);
    console.log($scope.desc);
    console.log($scope.num_scoring_keys);
    console.log($scope.num_questions);

    $scope.step_1_visible = false;
    $scope.step_2_visible = true;
    $scope.step_3_visible = false;

    /////////////////////////////////////////////////////////  STEP 1.2


    for (var n = 1; n <= $scope.num_scoring_keys; n++) {
      $scope.scoring_keys.push({
        'id'    : 'n',
        'name': 'Scoring Key ' + n,
        'score': '0'
      })
    }
    console.log($scope.scoring_keys);
  }

  /////////////////////////////////////////////////////////  STEP 2

  $scope.step_2 = function() {
    for (var i = 1; i <= $scope.num_questions; i++) {

      $scope.questions.push({
        'id': 'question' + i,
        'name': 'Question ' + i,
        'keys': $scope.scoring_keys,
        'answer': ''
      });
    }
    console.log($scope.questions);

    $scope.step_2_visible = false;
    $scope.step_3_visible = true;
  }

  $scope.step_3 = function() {
    console.log($scope.questions)
  }

  ///////////////////////////////////////////////////////// STEP 3

  for (var i = 1; i <= $scope.num_questions; i++) {

    $scope.questions.push({
      'id': 'question' + i,
      'name': 'Question ' + i,
      'keys': $scope.scoring_keys
    });
  }
  console.log($scope.questions);

  $scope.questionnaire.push($scope.name);
  $scope.questionnaire.push($scope.desc);
  $scope.questionnaire.push($scope.questions);

  ///////////////////////////////////////////////////////// STEP 3.2

  $scope.addNewQuestion = function() {
    var newItemNo = $scope.questions.length + 1;
    $scope.questions.push({
      'id': 'choice' + newItemNo,
      'name': 'choice' + newItemNo
    });
  };

  $scope.removeNewQuestion = function() {
    var newItemNo = $scope.questions.length - 1;
    if (newItemNo !== 0) {
      $scope.questions.pop();
    }
  };

  $scope.showAddQuestion = function(choice) {
    //return choice.id === $scope.choices[$scope.choices.length-1].id;
    return false;
  };

  $scope.step_3 = function() {
      console.log($scope.questions)
      $scope.answer = angular.toJson($scope.questions);
      localStorage.setItem("question", $scope.answer);
  }

});
