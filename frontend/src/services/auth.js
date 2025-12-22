import {
  CognitoUserPool,
  CognitoUser,
  AuthenticationDetails,
  CognitoUserAttribute,
} from 'amazon-cognito-identity-js';

const poolData = {
  UserPoolId: process.env.REACT_APP_COGNITO_USER_POOL_ID || 'us-east-1_sXWJaCWNJ',
  ClientId: process.env.REACT_APP_COGNITO_CLIENT_ID || '1qq6pvrdun6aojkbdce6slu5ra',
};

const userPool = new CognitoUserPool(poolData);

export const signUp = (email, password, name) => {
  return new Promise((resolve, reject) => {
    const attributeList = [
      new CognitoUserAttribute({
        Name: 'email',
        Value: email,
      }),
      new CognitoUserAttribute({
        Name: 'name',
        Value: name,
      }),
    ];

    userPool.signUp(email, password, attributeList, null, (err, result) => {
      if (err) {
        reject(err);
        return;
      }
      resolve(result.user);
    });
  });
};

export const confirmSignUp = (email, code) => {
  return new Promise((resolve, reject) => {
    const userData = {
      Username: email,
      Pool: userPool,
    };

    const cognitoUser = new CognitoUser(userData);

    cognitoUser.confirmRegistration(code, true, (err, result) => {
      if (err) {
        reject(err);
        return;
      }
      resolve(result);
    });
  });
};

export const resendConfirmationCode = (email) => {
  return new Promise((resolve, reject) => {
    const userData = {
      Username: email,
      Pool: userPool,
    };

    const cognitoUser = new CognitoUser(userData);

    cognitoUser.resendConfirmationCode((err, result) => {
      if (err) {
        reject(err);
        return;
      }
      resolve(result);
    });
  });
};

export const signIn = (email, password) => {
  return new Promise((resolve, reject) => {
    const authenticationData = {
      Username: email,
      Password: password,
    };

    const authenticationDetails = new AuthenticationDetails(authenticationData);

    const userData = {
      Username: email,
      Pool: userPool,
    };

    const cognitoUser = new CognitoUser(userData);

    cognitoUser.authenticateUser(authenticationDetails, {
      onSuccess: (result) => {
        const accessToken = result.getAccessToken().getJwtToken();
        const idToken = result.getIdToken().getJwtToken();
        localStorage.setItem('accessToken', accessToken);
        localStorage.setItem('idToken', idToken);
        resolve(result);
      },
      onFailure: (err) => {
        reject(err);
      },
    });
  });
};

export const signOut = () => {
  const cognitoUser = userPool.getCurrentUser();
  if (cognitoUser) {
    cognitoUser.signOut();
  }
  localStorage.removeItem('accessToken');
  localStorage.removeItem('idToken');
};

export const getCurrentUser = () => {
  return new Promise((resolve, reject) => {
    const cognitoUser = userPool.getCurrentUser();

    if (!cognitoUser) {
      reject(new Error('No user found'));
      return;
    }

    cognitoUser.getSession((err, session) => {
      if (err) {
        reject(err);
        return;
      }
      cognitoUser.getUserAttributes((err, attributes) => {
        if (err) {
          reject(err);
          return;
        }
        const userData = {};
        attributes.forEach((attribute) => {
          userData[attribute.Name] = attribute.Value;
        });
        resolve({ ...userData, username: cognitoUser.username });
      });
    });
  });
};

export const getAccessToken = () => {
  return localStorage.getItem('accessToken');
};

export const isAuthenticated = () => {
  const cognitoUser = userPool.getCurrentUser();
  return cognitoUser !== null && localStorage.getItem('accessToken') !== null;
};
