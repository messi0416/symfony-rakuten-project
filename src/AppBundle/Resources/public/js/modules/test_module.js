'use strict';

const TestModule = function(name, age) {
  this.name = name;
  this.age = age;
};
TestModule.prototype.talk = function() {
  console.log(this.name + ' is ' + this.age + ' years old !');
};

export default TestModule;
